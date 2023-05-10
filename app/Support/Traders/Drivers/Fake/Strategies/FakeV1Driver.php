<?php

namespace App\Support\Traders\Drivers\Fake\Strategies;

use App\Enums\FinancingOrderHistory;
use App\Enums\MediaCollections\TraderOrderMediaCollection;
use App\Exceptions\TraderException;
use App\Jobs\General\ProcessAskClientForWakala;
use App\Models\FinancingOrder;
use App\Models\TraderOrder;
use App\Support\Traders\Contracts\TraderInterface;
use App\Support\Traders\Drivers\Dmcc\Jobs\V1\ProcessDmccMpoOrder;
use App\Support\Traders\Drivers\Dmcc\Jobs\V1\ProcessDmccRespondedToPtpOrder;
use App\Support\Traders\Drivers\Dmcc\Jobs\V1\ProcessDmccSellingCommodityToCustomerOrder;
use App\Support\Traders\Traits\FakeTraderHelperTrait;
use Carbon\CarbonImmutable;
use Exception;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class FakeV1Driver implements TraderInterface
{
    use FakeTraderHelperTrait{
        createTraderOrder as traitCreateTraderOrder;
    }

    /**
     * @return bool
     */
    public function acceptAgreement(): bool
    {
        return true;
    }

    /**
     * @throws TraderException
     */
    public function createTraderOrder(FinancingOrder $financingOrder): string
    {
        $ttiId = $this->getTtiId($financingOrder);
        $traderOrder = $this->traitCreateTraderOrder($financingOrder, $ttiId, 'fake');
        $this->createTraderOrderHistory($traderOrder, FinancingOrderHistory::GetTtiId);

        return $ttiId;
    }

    /**
     * @throws TraderException
     */
    public function fetchOrderResult(string $type): ?array
    {
        return $this->fetchNotifications($type);
    }

    /**
     * @throws TraderException
     */
    public function fetchNotifications(string $type): ?array
    {
        $response = Http::get($this->buildUrl('notifications?type='.$type));

        if (! $response->successful()) {
            throw new TraderException(collect([
                'driver' => 'fake',
                'step' => 'fetchNotifications',
                'requestBody' => [
                    'type' => $type,
                ],
                'responseBody' => $response->body(),
            ]));
        }

        $data = collect($response->json())->map(function ($notification) {
            return (object) [
                'notificationHeaderAndEntity' => (object) [
                    'notificationId' => $notification['id'],
                    'notification' => $notification['notification'],
                    'notificationEntityDetails' => (object) [
                        'notificationEntity' => [
                            (object) [
                                'entityValue' => $notification['ttiId'],
                            ],
                        ],
                    ],
                ],
            ];
        });

        return $data->toArray();
    }

    /**
     * @throws TraderException
     */
    public function processNotification($notificationId): void
    {
        $response = Http::get($this->buildUrl('processNotification/'.$notificationId));

        if (! $this->isSuccess($response)) {
            throw new TraderException(collect([
                'driver' => 'fake',
                'step' => 'processNotification',
                'requestBody' => [
                    'notificationId' => $notificationId,
                ],
                'responseBody' => $response->body(),
            ]));
        }
    }

    private function buildUrl($path): string
    {
        return 'http://'.config('trader.providers.fake.username').':'.config('trader.providers.fake.password').'@'.config('trader.providers.fake.url').$path;
    }

    private function isSuccess(Response $response): bool
    {
        return $response->successful();
    }

    /**
     * @throws TraderException
     */
    public function getTtiId(FinancingOrder $financingOrder): mixed
    {
        $response = Http::post($this->buildUrl('getTTIIdForIssuePTP'), [
            'currency' => $financingOrder->currency,
            'costPrice' => $financingOrder->amount->formatByDecimal(),
            'profit' => $financingOrder->selling_price->subtract($financingOrder->amount)->formatByDecimal(),
            'paymentTerms' => config('trader.providers.fake.tti.payment_terms'),
            'unitOfDuration' => config('trader.providers.fake.tti.unit_of_duration'),
            'product' => null,
            'registeredMember' => config('trader.providers.fake.tti.registered_member'),
            'client' => null,
        ]);

        if (! $this->isSuccess($response)) {
            throw new TraderException(collect([
                'driver' => 'fake',
                'step' => 'getTtiId',
                'requestBody' => [
                    'currency' => $financingOrder->currency,
                    'costPrice' => $financingOrder->amount->formatByDecimal(),
                    'profit' => $financingOrder->selling_price->subtract($financingOrder->amount)->formatByDecimal(),
                    'paymentTerms' => config('trader.providers.fake.tti.payment_terms'),
                    'unitOfDuration' => config('trader.providers.fake.tti.unit_of_duration'),
                    'product' => null,
                    'registeredMember' => config('trader.providers.fake.tti.registered_member'),
                    'client' => null,
                ],
                'responseBody' => $response->body(),
                'financingOrderId' => $financingOrder->id,
            ]));
        }

        return $response->json('data.ttiId');
    }

    /**
     * @param  FinancingOrder  $financingOrder
     * @return bool
     */
    public function cancelOrder(FinancingOrder $financingOrder): bool
    {
        return true;
    }

    /**
     * @throws TraderException
     */
    public function respondPtpService(string $ttiId)
    {
        $response = Http::post($this->buildUrl('respondPTPService'), [
            'ttiId' => $ttiId,
            'comments' => 'create PTP',
            'submitAction' => 'true',
        ]);

        if (! $this->isSuccess($response)) {
            throw new TraderException(collect([
                'driver' => 'fake',
                'step' => 'respondPtpService',
                'requestBody' => [
                    'ttiId' => $ttiId,
                    'comments' => 'create PTP',
                    'submitAction' => 'true',
                ],
                'responseBody' => $response->body(),
            ]));
        }

        return $response->object();
    }

    /**
     * @param $traderOrder
     * @return void
     *
     * @throws TraderException
     */
    public function createSellingCommodityToCustomerDocument($traderOrder): void
    {
        try {
            $dateTime = $traderOrder->traderHistories()
                ->where('action', FinancingOrderHistory::ContractSigned)
                ->first()
                ->created_at
                ->toImmutable();

            $separator = ' و ';
            $products = collect($traderOrder->products);
            $amount = $traderOrder->order->selling_price->formatByDecimal();
            $customerName = $traderOrder->order->customer_name;
            $productName = $products->pluck('product')->implode($separator);
            $data['created_at'] = $dateTime->clone();

            $this->storeOrderDocumentAsPdf(
                'selling-commodity-to-customer',
                [
                    'reference_number' => $traderOrder->id,
                    'company_name' => $traderOrder->order->company()->withTrashed()->first()->name,
                    'order_number' => $traderOrder->financing_order_id,
                    'products' => $traderOrder->products,
                    'amount' => $amount,
                    'product_name' => $productName,
                    'customer_name' => $customerName,
                    'contract_signed_date' => $dateTime->tz('Asia/Riyadh')->toDateString(),
                    'contract_signed_time' => $dateTime->tz('Asia/Riyadh')->toTimeString(),
                ],
                $traderOrder,
                TraderOrderMediaCollection::SellingCommodityToCustomer,
            );

            $this->createTraderOrderHistory($traderOrder, FinancingOrderHistory::CreateSellingCommodityToCustomerDocument, $data);
        } catch (Exception $exception) {
            throw new TraderException(collect([
                'driver' => 'fake',
                'step' => 'createSellingCommodityToCustomerDocument',
                'requestBody' => [
                    'traderOrder' => $traderOrder,
                ],
                'responseBody' => $exception->getMessage(),
            ]));
        }
    }

    /**
     * @throws TraderException
     */
    public function getDocumentByTypeAndTransaction(string $ttiId, string $documentType): mixed
    {
        // request PTP document
        $response = Http::post($this->buildUrl('getDoumentByType'), [
            'ttiId' => $ttiId,
            'type' => $documentType,
        ]);

        if (! $this->isSuccess($response)) {
            throw new TraderException(collect([
                'driver' => 'fake',
                'step' => 'getDocumentByTypeAndTransaction',
                'requestBody' => [
                    'ttiId' => $ttiId,
                    'type' => $documentType,
                ],
                'responseBody' => $response->body(),
            ]));
        }

        return $response->json('data.fileContent');
    }

    /**
     * @throws TraderException
     */
    public function createTransferOwnershipToLenderDocument($traderOrder): void
    {
        try {
            $separator = ' و ';
            $products = collect($traderOrder->products);
            $amount = $traderOrder->order->amount->formatByDecimal();

            $previousOwner = $products->pluck('previous_owner')->implode($separator);
            $productName = $products->pluck('product')->implode($separator);
            $date = CarbonImmutable::now();
            $data['created_at'] = $date;

            $this->storeOrderDocumentAsPdf(
                'transfer-ownership-to-lender',
                [
                    'order_id' => $traderOrder->order->id,
                    'products' => $traderOrder->products,
                    'reference_number' => $traderOrder->id,
                    'company_name' => $traderOrder->order->company()->withTrashed()->first()?->name,
                    'order_number' => $traderOrder->financing_order_id,
                    'amount' => $amount,
                    'previous_owner' => $previousOwner,
                    'product_name' => $productName,
                    'date' => $date->tz('Asia/Riyadh')->toDateString(),
                    'time' => $date->tz('Asia/Riyadh')->toTimeString(),
                ],
                $traderOrder,
                TraderOrderMediaCollection::TransferOwnershipToLender
            );

            $this->createTraderOrderHistory($traderOrder, FinancingOrderHistory::CreateTransferOwnershipToLenderDocument, $data);
        } catch (Exception $exception) {
            throw new TraderException(collect([
                'driver' => 'fake',
                'step' => 'createTransferOwnershipToLenderDocument',
                'requestBody' => [
                    'traderOrder' => $traderOrder,
                ],
                'responseBody' => $exception->getMessage(),
            ]));
        }
    }

    /**
     * @throws TraderException
     */
    public function getInventoryBasket(TraderOrder $traderOrder): object
    {
        $data = [
            'products' => [
                [
                    'product' => 'Yogurt',
                    'quantity' => '10',
                    'amount' => $traderOrder->order->amount->formatByDecimal(),
                    'currency' => 'SAR',
                    'warehouse' => 'Warehouse',
                    'owner' => 'Owner 1',
                    'previous_owner' => 'Owner 0',
                    'new_owner' => 'Owner 1',
                    'date_time_of_purchasing_commodity' => '2023-01-01 00:00:00',
                    'warehouse_or_vault_emirates' => 'Emirates',
                    'warehouse_or_vault_country' => 'Saudi Arabia',
                    'inventory_record_id' => '1000',
                    'warrant_percentage' => '100',
                    'warrant_no' => '658',
                    'hs_code' => '#234',
                    'uom' => 'Kilo',
                ],
            ],
        ];

        $data['exchange_rate'] = '3.75';

        $traderOrder->update($data);

        return (object) $data;
    }

    public function uploadTTIDocumentAndGetVersionNumber(string $ttiId): string
    {
        return '001';
    }

    /**
     * @throws TraderException
     */
    public function issueMurabahaPurchaseOffer(string $ttiId, string $versionNo): void
    {
        $response = Http::post($this->buildUrl('issueMurabahaPurchaseOffer'), [
            'ttiId' => $ttiId,
            'comments' => 'create MPO',
            'ttiDocumentVersionNo' => $versionNo,
        ]);

        if (! $this->isSuccess($response)) {
            throw new TraderException(collect([
                'driver' => 'fake',
                'step' => 'issueMurabahaPurchaseOffer',
                'requestBody' => [
                    'ttiId' => $ttiId,
                    'comments' => 'create MPO',
                    'ttiDocumentVersionNo' => $versionNo,
                ],
                'responseBody' => $response->body(),
            ]));
        }
    }

    public function transferOwnershipToCustomer(TraderOrder $traderOrder)
    {
        // TODO: Implement ownershipToCustomer() method.
    }

    public function sellingCommodityToOpenMarket(TraderOrder $traderOrder)
    {
        // TODO: Implement sellingCommodity() method.
    }

    public function dispatchJobForTransitioningFlow(TraderOrder $traderOrder)
    {
        match ((int) $traderOrder->last_history_action) {
            FinancingOrderHistory::RespondPtp => ProcessDmccRespondedToPtpOrder::dispatch($traderOrder->id),
            FinancingOrderHistory::ContractSigned => ProcessAskClientForWakala::dispatch($traderOrder->id),
            FinancingOrderHistory::ClientWakalaAccepted => ProcessDmccSellingCommodityToCustomerOrder::dispatch($traderOrder->id),
            FinancingOrderHistory::CreateSellingCommodityToCustomerDocument => ProcessDmccMpoOrder::dispatch($traderOrder->id),
            default => null,
        };
    }
}
