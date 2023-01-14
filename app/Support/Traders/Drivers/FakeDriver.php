<?php

namespace App\Support\Traders\Drivers;

use App\Enums\FinancingOrderHistory;
use App\Enums\MediaCollections\FinancingOrderMediaCollection;
use App\Exceptions\TraderException;
use App\Models\FinancingOrder;
use App\Support\Traders\Contracts\TraderInterface;
use App\Support\Traders\TraderHelper;
use Carbon\Carbon;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class FakeDriver implements TraderInterface
{
    use TraderHelper;

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
    public function respondPtpService(string $ttiId): void
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
    }

    /**
     * @param $traderOrder
     * @return void
     */
    public function createSellingCommodityToCustomerDocument($traderOrder): void
    {
        $this->createOrderDocumentAsPdf(
            'selling-commodity-to-customer',
            [
                'ttiId' => $traderOrder->reference,
                'companyName' => $traderOrder->order->company->name,
                'orderNumber' => $traderOrder->financing_order_id,
                'amount' => $traderOrder->order->amount->formatByDecimal(),
                'hsCodeDescription' => 'product description',
                'quantity' => 100,
                'warehouse' => 'warehouse',
                'owner' => 'owner',
                'date' => Carbon::now()->toDateString(),
                'time' => Carbon::now()->toTimeString(),
            ],
            $traderOrder,
            FinancingOrderMediaCollection::SellingCommodityToCustomer,
            FinancingOrderHistory::CreateSellingCommodityToCustomerDocument
        );
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

    public function createTransferOwnershipToLenderDocument($traderOrder): void
    {
        $this->createOrderDocumentAsPdf(
            'transfer-ownership-to-lender',
            [
                'ttiId' => $traderOrder->reference,
                'companyName' => $traderOrder->order->company->name,
                'orderNumber' => $traderOrder->financing_order_id,
                'amount' => $traderOrder->order->amount->formatByDecimal(),
                'hsCodeDescription' => 'product description',
                'quantity' => 100,
                'warehouse' => 'warehouse',
                'owner' => 'owner',
                'date' => Carbon::now()->toDateString(),
                'time' => Carbon::now()->toTimeString(),
            ],
            $traderOrder,
            FinancingOrderMediaCollection::TransferOwnershipToLender,
            FinancingOrderHistory::CreateTransferOwnershipToLenderDocument
        );
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
