<?php

namespace App\Console\Commands;

use App\Enums\FinancingOrderHistory;
use App\Enums\MediaCollections\TraderOrderMediaCollection;
use App\Enums\TraderOrderMode;
use App\Models\TraderOrder;
use App\Support\DataTransferObjects\CommodityProductDto;
use App\Support\Traders\Traits\TraderHelperTrait;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Traits\Localizable;

class OverwriteOldLenderCertificateForBursaV2Orders extends Command
{
    use Localizable, TraderHelperTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bursa:regenerate-lender-certs';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Overwrite old lender certs to have supplier as field';

    protected $failedOrdersIds = [];

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        TraderOrder::query()
            ->orderBy('id')
            ->where('mode', TraderOrderMode::Automatic)
            ->where('version', 'v2')
            ->where('provider', 'bursam')
            ->chunk(100, function ($traderOrders) {
                $traderOrders->map(function ($traderOrder) {
                    $response = Http::bursam()->post(
                        'api/process/svc/bsas/bidXML.json',
                        [
                            'input' => [
                                'membershortname' => config('trader.providers.bursam.member_short_name'),
                                'ecertno' => $traderOrder->reference,
                            ],
                        ]
                    );

                    if ($response->json('SUCCESSYN') == 'N') {
                        $this->failedOrdersIds[] = $traderOrder->id;

                        return;
                    }

                    DB::transaction(function () use ($traderOrder, $response) {
                        $traderOrder->update([
                            'products' => [
                                (new CommodityProductDto(
                                    product: $response->json('PNAME'),
                                    quantity: $response->json('PVOLUME'),
                                    amount: $response->json('TOTALVALUE'),
                                    previous_owner: Arr::pluck($response->json('LINE'), 'SUPPLIER'),
                                    date_time_of_purchasing_commodity: $response->json('PURCHASETIMEDATE'),
                                    uom: collect($traderOrder->original_data)->get('unit'),
                                    currency: $response->json('CURRENCY')
                                ))->toArray(),
                            ],
                        ]);

                        $this->withLocale('ar', function () use ($traderOrder) {
                            $amount = $traderOrder->order->amount->formatByDecimal();

                            $history = $traderOrder->traderHistories()
                                ->where('action', FinancingOrderHistory::CreateTransferOwnershipToLenderDocument)
                                ->first();

                            if ($history === null) {
                                return;
                            }

                            $timeInstanceInUtcTz = $history->created_at->toImmutable();
                            $timeInstanceInAsiaRiyadhTz = $timeInstanceInUtcTz->timezone('Asia/Riyadh');
                            $products = collect($traderOrder->products)->map(fn ($product) => CommodityProductDto::fromArray($product));

                            $this->storeOrderDocumentAsPdf(
                                'transfer-ownership-to-lender',
                                [
                                    'order_id' => $traderOrder->order->id,
                                    'products' => $this->transformProductsToCommodityProductsDTO($traderOrder->products),
                                    'reference_number' => $traderOrder->id,
                                    'trader_order_reference' => $traderOrder->reference,
                                    'company_name' => $traderOrder->order->company()->withTrashed()->first()->name,
                                    'order_number' => $traderOrder->financing_order_id,
                                    'amount' => $amount,
                                    'previous_owner' => $products->map(
                                        fn ($item) => $item->getPreviousOwnerAsArray()
                                    )
                                        ->flatten()
                                        ->implode('،'),
                                    'product_name' => $products->implode(fn ($item) => $item->getProduct(), '،'),
                                    'date' => $timeInstanceInAsiaRiyadhTz->toDateString(),
                                    'time' => $timeInstanceInAsiaRiyadhTz->toTimeString(),
                                ],
                                $traderOrder,
                                TraderOrderMediaCollection::TransferOwnershipToLender
                            );
                        });
                    });
                });
            });

        return Command::SUCCESS;
    }
}
