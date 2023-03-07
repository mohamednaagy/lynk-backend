<?php

namespace App\Support\Traders\Drivers;

use App\Enums\FinancingOrderHistory;
use App\Enums\MediaCollections\TraderOrderMediaCollection;
use App\Exceptions\TraderException;
use App\Models\FinancingOrder;
use App\Models\TraderOrder;
use App\Support\Traders\Contracts\TraderInterface;
use App\Support\Traders\TraderHelperTrait;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class FakeDriver implements TraderInterface
{
    use TraderHelperTrait;

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
    public function getTti(FinancingOrder $financingOrder): string
    {
        $ttiId = $this->getTtiId($financingOrder);
        $traderOrder = $this->createTraderOrder($financingOrder, $ttiId, 'fake');
        $this->createTraderOrderHistory($traderOrder, FinancingOrderHistory::GetTtiId);

        return $ttiId;
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
            $this->storeOrderDocumentAsPdf(
                'selling-commodity-to-customer',
                [
                    'products' => $traderOrder->products,
                    'reference_number' => $traderOrder->id,
                    'company_name' => $traderOrder->order->company->name,
                    'order_number' => $traderOrder->financing_order_id,
                    'amount' => $traderOrder->order->amount->formatByDecimal(),
                    'hs_code_description' => 'Solid yogurt (jameed).',
                    'quantity' => 100,
                    'uom' => 'MTT',
                    'warehouse' => 'DMC03-S-0038',
                    // TODO: change later after fix from business
                    'new_owner' => 'محمد علي',
                    'date' => Carbon::now()->toDateString(),
                    'time' => Carbon::now()->toTimeString(),
                ],
                $traderOrder,
                TraderOrderMediaCollection::SellingCommodityToCustomer,
            );

            $this->createTraderOrderHistory($traderOrder, FinancingOrderHistory::CreateSellingCommodityToCustomerDocument);
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
        $dateTime = Carbon::createFromFormat('Y-m-d H:i:s', $traderOrder->products[0]['date_time_of_purchasing_commodity']);

        try {
            $this->storeOrderDocumentAsPdf(
                'transfer-ownership-to-lender',
                [
                    'products' => $traderOrder->products,
                    'reference_number' => $traderOrder->id,
                    'company_name' => $traderOrder->order->company->name,
                    'order_number' => $traderOrder->financing_order_id,
                    'amount' => $traderOrder->order->amount->formatByDecimal(),
                    'date' => $dateTime->toDateString(),
                    'time' => $dateTime->toTimeString(),
                ],
                $traderOrder,
                TraderOrderMediaCollection::TransferOwnershipToLender,
            );

            $this->createTraderOrderHistory($traderOrder, FinancingOrderHistory::CreateTransferOwnershipToLenderDocument);
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
                    'amount' => '1000',
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
                [
                    'product' => 'Yogurt 2',
                    'quantity' => '5',
                    'amount' => '500',
                    'currency' => 'SAR',
                    'warehouse' => 'Warehouse',
                    'owner' => 'Owner 1',
                    'previous_owner' => 'Owner 2',
                    'new_owner' => 'Owner 3',
                    'date_time_of_purchasing_commodity' => '2023-02-01 00:00:00',
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
}
