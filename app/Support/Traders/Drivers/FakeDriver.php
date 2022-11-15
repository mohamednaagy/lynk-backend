<?php

namespace App\Support\Traders\Drivers;

use App\Enums\FinancingOrderHistory;
use App\Enums\MediaCollections\FinancingOrderMediaCollection;
use App\Exceptions\TraderException;
use App\Models\FinancingOrder;
use App\Support\PdfGenerator\PdfGenerator;
use App\Support\Traders\Contracts\TraderInterface;
use App\Support\Traders\TraderHelper;
use CodeDredd\Soap\Facades\Soap;
use CodeDredd\Soap\SoapClient;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class FakeDriver implements TraderInterface
{
    use TraderHelper;

    private SoapClient $soap;

    public function __construct()
    {
        $this->soap = Soap::buildClient('dmcc');
    }

    public function acceptAgreement(): bool
    {
        return true;
    }

    public function getTti(FinancingOrder $financingOrder): string
    {
        $ttiId = $this->getTtiId($financingOrder);
        $traderOrder = $this->createTraderOrder($financingOrder, $ttiId, 'fake');
        $this->createTraderOrderHistory($traderOrder, FinancingOrderHistory::GetTtiId);

        return $ttiId;
    }

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
            'currency' => 'SAR',
            'costPrice' => $financingOrder->amount,
            'profit' => $financingOrder->selling_price - $financingOrder->amount,
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
                    'currency' => 'SAR',
                    'costPrice' => $financingOrder->amount,
                    'profit' => $financingOrder->selling_price - $financingOrder->amount,
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
     * @throws TraderException
     */
    public function cancelOrder(FinancingOrder $financingOrder): mixed
    {
        $traderOrder = $financingOrder->activeTraderOrder()->first();
        $response = $this->soap
            ->baseWsdl($this->buildUrl('cancelTTI'))
            ->call('cancelTTI', [
                'ttiId' => $traderOrder->reference,
                'comments' => 'Cancel Order',
                'confirmAction' => 'true',
            ]);

        if (! $this->isSuccess($response)) {
            throw new TraderException(collect([
                'driver' => 'fake',
                'step' => 'cancelOrder',
                'requestBody' => [
                    'ttiId' => $traderOrder->reference,
                    'comments' => 'Cancel Order',
                    'confirmAction' => 'true',
                ],
                'responseBody' => $response->body(),
                'financingOrderId' => $financingOrder->id,
                'traderOrderId' => $traderOrder->id,
            ]));
        }

        return $response->object();
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

    public function createSellingCommodityToCustomerDocument($traderOrder): void
    {
        $html = view('selling-commodity-to-customer')->render();
        $path = $traderOrder->financing_order_id.'/DMCC-SCTC/'.$traderOrder->reference.'.pdf';
        PdfGenerator::outputFromHtml($html, $path, [
            'gotoOptions' => ['waitUntil' => 'networkidle0'],
        ]);

        $this->attachDocumentToOrder($traderOrder, storage_path('app/'.$path), FinancingOrderMediaCollection::SellingCommodityToCustomer);
        $this->createTraderOrderHistory($traderOrder, FinancingOrderHistory::CreateSellingCommodityToCustomerDocument);
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

    public function attachDocumentToOrder($traderOrder, $document, $collectionName, $type = null): void
    {
        if (! is_null($type)) {
            $traderOrder->order->addMediaFromBase64(
                $document
            )->usingFileName('.pdf')->toMediaCollection($collectionName);
        } else {
            $traderOrder->order->addMedia(
                $document
            )->toMediaCollection($collectionName);
        }
    }

    public function createTransferOwnershipToLenderDocument($traderOrder): void
    {
        $html = view('transfer-ownership-to-lender')->render();
        $path = $traderOrder->financing_order_id.'/DMCC-TOTL/'.$traderOrder->reference.'.pdf';
        PdfGenerator::outputFromHtml($html, $path, [
            'gotoOptions' => ['waitUntil' => 'networkidle0'],
        ]);

        $this->attachDocumentToOrder($traderOrder, storage_path('app/'.$path), FinancingOrderMediaCollection::TransferOwnershipToLender);
    }

    public function uploadTTIDocumentAndGetVersionNumber(string $ttiId): mixed
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
