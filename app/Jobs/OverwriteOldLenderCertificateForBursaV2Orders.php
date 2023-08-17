<?php

namespace App\Jobs;

use App\Enums\FinancingOrderHistory;
use App\Enums\MediaCollections\TraderOrderMediaCollection;
use App\Models\TraderOrder;
use App\Support\DataTransferObjects\CommodityProductDto;
use App\Support\Traders\Traits\TraderHelperTrait;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Traits\Localizable;

class OverwriteOldLenderCertificateForBursaV2Orders implements ShouldQueue
{
    use Localizable, TraderHelperTrait, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bursam:regenerate-lender-certs';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Overwrite old lender certs to have supplier as field';

    public function __construct(protected $traderOrderId)
    {
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $traderOrder = TraderOrder::findOrFail($this->traderOrderId);

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
            throw new \Exception(sprintf('Trader order failed %s', $traderOrder->id));
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
    }
}
