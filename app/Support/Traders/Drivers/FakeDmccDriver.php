<?php

namespace App\Support\Traders\Drivers;

use App\Enums\FinancingOrderHistory;
use App\Enums\MediaCollections\FinancingOrderMediaCollection;
use App\Enums\TraderOrderStatus;
use App\Models\FinancingOrder;
use App\Models\TraderOrder;
use App\Support\PdfGenerator\PdfGenerator;
use App\Support\Traders\Contracts\TraderInterface;
use CodeDredd\Soap\Facades\Soap;
use CodeDredd\Soap\SoapClient;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use stdClass;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

class FakeDmccDriver implements TraderInterface
{
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
        $traderOrder = $this->createTraderOrder($financingOrder, $ttiId);
        $this->createTraderOrderHistory($traderOrder, FinancingOrderHistory::GetTtiId);

        return $ttiId;
    }

    public function fetchNotification(string $type): ?array
    {
        $response = Http::get($this->prefixUrl('notifications?type='.$type));

        if (! $response->successful()) {
            throw new UnprocessableEntityHttpException();
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

    public function processFetchNotification($notificationId): void
    {
        $response = Http::get($this->prefixUrl('processNotification/'.$notificationId));

        if (! $this->isSuccess($response)) {
            throw new UnprocessableEntityHttpException();
        }
    }

    private function prefixUrl($url): string
    {
        return 'http://'.config('trader.providers.fake_dmcc.username').':'.config('trader.providers.fake_dmcc.password').'@'.config('trader.providers.fake_dmcc.url').$url;
    }

    private function isSuccess(Response $response): bool
    {
        return $response->successful();
    }

    public function getTtiId(FinancingOrder $financingOrder): mixed
    {
        $response = Http::post($this->prefixUrl('getTTIIdForIssuePTP'), [
            'currency' => 'SAR',
            'costPrice' => 100,
            'profit' => 50,
            'paymentTerms' => config('trader.providers.dmcc.tti.payment_terms'),
            'unitOfDuration' => config('trader.providers.dmcc.tti.unit_of_duration'),
            'product' => null,
            'registeredMember' => config('trader.providers.dmcc.tti.registered_member'),
            'client' => null,
        ]);

        if (! $this->isSuccess($response)) {
            throw new UnprocessableEntityHttpException();
        }

        return $response->json('data.ttiId');
    }

    // TODO
    public function cancelTtiId(FinancingOrder $financingOrder): mixed
    {
        $traderOrder = $financingOrder->traderOrders()->latest()->first();
        $response = $this->soap
            ->baseWsdl($this->prefixUrl('cancelTTI'))
            ->call('cancelTTI', [
                'ttiId' => $traderOrder->reference,
                'comments' => 'Cancel Order',
                'confirmAction' => 'true',
            ]);

        if (! $this->isSuccess($response)) {
            throw new UnprocessableEntityHttpException();
        }

        return $response->object();
    }

    private function createTraderOrder(FinancingOrder $financingOrder, string $ttiId): Model|TraderOrder
    {
        return $financingOrder->traderOrders()->create([
            'provider' => 'dmcc',
            'reference' => $ttiId,
            'status' => TraderOrderStatus::InProgress,
        ]);
    }

    public function respondPtpService(string $ttiId): void
    {
        $response = Http::post($this->prefixUrl('respondPTPService'), [
            'ttiId' => $ttiId,
            'comments' => 'create PTP',
            'submitAction' => 'true',
        ]);

        if (! $this->isSuccess($response)) {
            throw new UnprocessableEntityHttpException();
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

    public function getDocumentByTypeAndTransaction(string $ttiId, string $documentType): mixed
    {
        // request PTP document
        $response = Http::post($this->prefixUrl('getDoumentByType'), [
            'ttiId' => $ttiId,
            'type' => $documentType,
        ]);

        if (! $this->isSuccess($response)) {
            throw new UnprocessableEntityHttpException();
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

    public function updateOrderStatus($order, int $status): void
    {
        $order->update([
            'status' => $status,
        ]);
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

    public function createTraderOrderHistory(TraderOrder $traderOrder, int $action): void
    {
        $traderOrder->traderHistories()->create([
            'action' => $action,
        ]);
    }

    public function uploadTTIDocumentAndGetVersionNumber(string $ttiId): mixed
    {
        return '001';
    }

    public function issueMurabahaPurchaseOffer(string $ttiId, string $versionNo): void
    {
        $response = Http::post($this->prefixUrl('issueMurabahaPurchaseOffer'), [
            'ttiId' => $ttiId,
            'comments' => 'create MPO',
            'ttiDocumentVersionNo' => $versionNo,
        ]);

        if (! $this->isSuccess($response)) {
            throw new UnprocessableEntityHttpException();
        }
    }

    private function arrayToObject($array)
    {
        $obj = new stdClass();

        foreach ($array as $k => $v) {
            if (strlen($k)) {
                if (is_array($v) && ! $this->isAssoc($v)) {
                    $obj->{$k} = $this->arrayToObject($v); //RECURSION
                } else {
                    $obj->{$k} = $v;
                }
            }
        }

        return $obj;
    }

    public function isAssoc(array $arr)
    {
        if ([] === $arr) {
            return false;
        }

        return array_keys($arr) !== range(0, count($arr) - 1);
    }
}
