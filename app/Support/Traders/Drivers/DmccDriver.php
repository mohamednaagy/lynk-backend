<?php

namespace App\Support\Traders\Drivers;

use App\Enums\FinancingOrderHistory;
use App\Enums\MediaCollections\FinancingOrderMediaCollection;
use App\Exceptions\TraderException;
use App\Models\FinancingOrder;
use App\Models\TraderOrder;
use App\Support\PdfGenerator\PdfGenerator;
use App\Support\Traders\Contracts\TraderInterface;
use App\Support\Traders\TraderHelper;
use Carbon\Carbon;
use CodeDredd\Soap\Client\Response;
use CodeDredd\Soap\Facades\Soap;
use CodeDredd\Soap\SoapClient;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;

class DmccDriver implements TraderInterface
{
    use TraderHelper;

    private SoapClient $soap;

    public function __construct()
    {
        $this->soap = Soap::buildClient('dmcc');
    }

    public function acceptAgreement(): bool
    {
        $response = $this->soap
            ->baseWsdl($this->prefixUrl('getClickThroughAgreement'))
            ->call('getClickThroughAgreement');

        if ($error = $response->collect()->get('errorMessage')) {
            throw new RuntimeException($error);
        }

        $response = $this->soap
            ->baseWsdl($this->prefixUrl('acceptRejectClickThroughAgreement'))
            ->call('acceptRejectClickThroughAgreement', [
                'acceptReject' => 'true',
            ]);

        return $this->isSuccess($response);
    }

    public function getTti(FinancingOrder $financingOrder): string
    {
        $ttiId = $this->getTtiId($financingOrder);

        if (! blank($ttiId)) {
            $traderOrder = $this->createTraderOrder($financingOrder, $ttiId, 'dmcc');
            $this->createTraderOrderHistory($traderOrder, FinancingOrderHistory::GetTtiId);
        }

        return $ttiId;
    }

    /**
     * @throws TraderException
     */
    public function fetchNotifications(string $type): ?array
    {
        $response = $this->soap
            ->baseWsdl($this->prefixUrl('notificationDetailsRequest'))
            ->call('notificationDetailsRequest', [
                'notificationType' => $type,
            ]);

        if (! $response->successful()) {
            throw new TraderException(collect([
                'driver' => 'dmcc',
                'step' => 'fetchNotifications',
                'requestBody' => [
                    'notificationType' => $type,
                ],
                'responseBody' => $response->body(),
            ]));
        }

        return $response->object()->NotificationAllDetailsResponse[0]->notificationAllDetailsResponse->notificationDetails ?? [];
    }

    /**
     * @throws TraderException
     */
    public function processNotification($notificationId): void
    {
        $response = $this->soap
            ->baseWsdl($this->prefixUrl('processNotification'))
            ->call('processNotification', [
                'notificationId' => $notificationId,
            ]);

        if (! $response->successful()) {
            throw new TraderException(collect([
                'driver' => 'dmcc',
                'step' => 'processNotification',
                'requestBody' => [
                    'notificationId' => $notificationId,
                ],
                'responseBody' => $response->body(),
            ]));
        }
    }

    private function prefixUrl($url): string
    {
        return 'https://'.config('trader.providers.dmcc.username').':'.config('trader.providers.dmcc.password').'@na2.ai.dm-us.informaticacloud.com/active-bpel/soap/'.$url.'?wsdl';
    }

    private function isSuccess(Response $response): bool
    {
        return $response->successful() && $response->json()['successCode'] === '0000';
    }

    /**
     * @throws TraderException
     */
    public function getTtiId(FinancingOrder $financingOrder): mixed
    {
        Log::debug('getTTiId2', [
            'currency' => $financingOrder->currency,
            'costPrice' => $financingOrder->amount->formatByDecimal(),
            'profit' => $financingOrder->selling_price->subtract($financingOrder->amount)->formatByDecimal(),
            'paymentTerms' => config('trader.providers.dmcc.tti.payment_terms'),
            'unitOfDuration' => config('trader.providers.dmcc.tti.unit_of_duration'),
            'product' => null,
            'registeredMember' => config('trader.providers.dmcc.tti.registered_member'),
            'client' => null,
        ]);

        $response = $this->soap
            ->baseWsdl($this->prefixUrl('getTTIIDForIssuePTP'))
            ->call('getTTIIDForIssuePTP', [
                'currency' => $financingOrder->currency,
                'costPrice' => $financingOrder->amount->formatByDecimal(),
                'profit' => $financingOrder->selling_price->subtract($financingOrder->amount)->formatByDecimal(),
                'paymentTerms' => config('trader.providers.dmcc.tti.payment_terms'),
                'unitOfDuration' => config('trader.providers.dmcc.tti.unit_of_duration'),
                'product' => null,
                'registeredMember' => config('trader.providers.dmcc.tti.registered_member'),
                'client' => null,
            ])->object();

        Log::debug('getTTiId', [$response]);

        if (! isset($response->ttiId)) {
            throw new TraderException(collect([
                'driver' => 'dmcc',
                'step' => 'getTtiId',
                'requestBody' => [
                    'currency' => $financingOrder->currency,
                    'costPrice' => $financingOrder->amount->formatByDecimal(),
                    'profit' => $financingOrder->selling_price->subtract($financingOrder->amount)->formatByDecimal(),
                    'paymentTerms' => config('trader.providers.dmcc.tti.payment_terms'),
                    'unitOfDuration' => config('trader.providers.dmcc.tti.unit_of_duration'),
                    'product' => null,
                    'registeredMember' => config('trader.providers.dmcc.tti.registered_member'),
                    'client' => null,
                ],
                'responseBody' => $response->body(),
                'financingOrderId' => $financingOrder->id,
            ]));
        }

        return $response->ttiId;
    }

    /**
     * @throws TraderException
     */
    public function cancelOrder(FinancingOrder $financingOrder): mixed
    {
        $traderOrder = $financingOrder->activeTraderOrder()->first();
        $response = $this->soap
            ->baseWsdl($this->prefixUrl('cancelTTI'))
            ->call('cancelTTI', [
                'ttiId' => $traderOrder->reference,
                'comments' => 'Cancel Order',
                'confirmAction' => 'true',
            ]);

        if (! $this->isSuccess($response)) {
            throw new TraderException(collect([
                'driver' => 'dmcc',
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
        $response = $this->soap
            ->baseWsdl($this->prefixUrl('respondPTPService'))
            ->call('respondPTPService', [
                'ttiId' => $ttiId,
                'comments' => 'create PTP',
                'submitAction' => 'true',
            ]);

        if (! $this->isSuccess($response)) {
            throw new TraderException(collect([
                'driver' => 'dmcc',
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
     * @throws TraderException
     */
    public function createSellingCommodityToCustomerDocument($traderOrder): void
    {
        $response = $this->getInventoryBasket($traderOrder->reference);

        $html = view('selling-commodity-to-customer', [
            'ttiId' => $traderOrder->reference,
            'companyName' => $traderOrder->order->company->name,
            'orderNumber' => $traderOrder->financing_order_id,
            'amount' => $response->inventoryDetails[0]->totalValue.' '.$response->inventoryDetails[0]->currency,
            'hsCodeDescription' => $response->inventoryDetails[0]->hsCodeDescription,
            'quantity' => $response->inventoryDetails[0]->quantity,
            'warehouse' => $response->inventoryDetails[0]->warehouseOrVaultId,
            'owner' => $response->inventoryDetails[0]->owner,
            'date' => Carbon::now()->toDateString(),
            'time' => Carbon::now()->toTimeString(),
        ])->render();

        PdfGenerator::outputFromHtml($html, function ($fileResource) use ($traderOrder) {
            $this->attachDocumentToOrder(
                $traderOrder,
                $fileResource,
                FinancingOrderMediaCollection::SellingCommodityToCustomer
            );
        });

        $this->createTraderOrderHistory($traderOrder, FinancingOrderHistory::CreateSellingCommodityToCustomerDocument);
    }

    /**
     * @throws TraderException
     */
    public function getDocumentByTypeAndTransaction(string $ttiId, string $documentType): mixed
    {
        // request PTP document
        $response = $this->soap
            ->baseWsdl($this->prefixUrl('getDocumentByTypeAndTransaction'))
            ->call('getDocumentByTypeAndTransaction', [
                'ttiId' => $ttiId,
                'documentType' => $documentType,
            ]);

        if (! isset($response->object()->getdocument[0]->getDocumentByTypeResponse[0]->document)) {
            throw new TraderException(collect([
                'driver' => 'dmcc',
                'step' => 'getDocumentByTypeAndTransaction',
                'requestBody' => [
                    'ttiId' => $ttiId,
                    'documentType' => $documentType,
                ],
                'responseBody' => $response->body(),
            ]));
        }

        return $response->object()->getdocument[0]->getDocumentByTypeResponse[0]->document;
    }

    public function attachDocumentToOrder($traderOrder, $document, $collectionName, $type = null): void
    {
        $fileName = $traderOrder->provider.'-'.$traderOrder->reference.'.pdf';
        if (! is_null($type)) {
            $traderOrder->order->addMediaFromBase64(
                $document
            )->usingFileName($fileName)->toMediaCollection($collectionName);
        } else {
            $traderOrder->order->addMediaFromStream(
                $document
            )->usingFileName($fileName)->toMediaCollection($collectionName);
        }
    }

    /**
     * @throws TraderException
     */
    public function createTransferOwnershipToLenderDocument($traderOrder): void
    {
        $response = $this->getInventoryBasket($traderOrder->reference);

        $html = view('transfer-ownership-to-lender', [
            'ttiId' => $traderOrder->reference,
            'companyName' => $traderOrder->order->company->name,
            'orderNumber' => $traderOrder->financing_order_id,
            'amount' => $response->inventoryDetails[0]->totalValue.' '.$response->inventoryDetails[0]->currency,
            'hsCodeDescription' => $response->inventoryDetails[0]->hsCodeDescription,
            'quantity' => $response->inventoryDetails[0]->quantity,
            'warehouse' => $response->inventoryDetails[0]->warehouseOrVaultId,
            'owner' => $response->inventoryDetails[0]->owner,
            'date' => Carbon::now()->toDateString(),
            'time' => Carbon::now()->toTimeString(),
        ])->render();

        PdfGenerator::outputFromHtml($html, function ($fileResource) use ($traderOrder) {
            $this->attachDocumentToOrder(
                $traderOrder,
                $fileResource,
                FinancingOrderMediaCollection::TransferOwnershipToLender
            );
        });
    }

    /**
     * @throws TraderException
     */
    private function getInventoryBasket(string $ttiId): object
    {
        $response = $this->soap
            ->baseWsdl($this->prefixUrl('getInventoryBasket'))
            ->call('getInventoryBasket', [
                'ttiId' => $ttiId,
            ]);

        if ($response->object()->errorCode != '') {
            throw new TraderException(collect([
                'driver' => 'dmcc',
                'step' => 'getInventoryBasket',
                'requestBody' => [
                    'ttiId' => $ttiId,
                ],
                'responseBody' => $response->body(),
            ]));
        }

        $details = $response->object()->inventoryDetails[0];

        $traderOrder = TraderOrder::query()->where('reference', $ttiId)->first();
        $traderOrder->update([
            'product' => $details->hsCodeDescription,
            'quantity' => $details->quantity,
            'amount' => $details->totalValue.' '.$details->currency,
            'warehouse' => $details->warehouseOrVaultId,
            'owner' => $details->owner,
            'previousOwner' => $details->previousOwner,
            'newOwner' => $details->newOwner,
            'dateTimeOfPurchasingCommodity' => $details->dateTimeOfPurchasingCommodity,
            'warehouseOrVaultEmirates' => $details->warehouseOrVaultEmirates,
            'warehouseOrVaultCountry' => $details->warehouseOrVaultCountry,
        ]);

        return $response;
    }

    /**
     * @throws TraderException
     */
    public function uploadTTIDocumentAndGetVersionNumber(string $ttiId): mixed
    {
        // upload TTIDocument for now it sample PDF to get version
        $response = $this->soap
            ->baseWsdl($this->prefixUrl('uploadTTIDocument'))
            ->call('uploadTTIDocument', [
                'ttiDocumentName' => $ttiId.Str::random(5),
                'ttiDocument' => 'JVBERi0xLjMNCiXi48/TDQoNCjEgMCBvYmoNCjw8DQovVHlwZSAvQ2F0YWxvZw0KL091dGxpbmVzIDIgMCBSDQovUGFnZXMgMyAwIFINCj4+DQplbmRvYmoNCg0KMiAwIG9iag0KPDwNCi9UeXBlIC9PdXRsaW5lcw0KL0NvdW50IDANCj4+DQplbmRvYmoNCg0KMyAwIG9iag0KPDwNCi9UeXBlIC9QYWdlcw0KL0NvdW50IDINCi9LaWRzIFsgNCAwIFIgNiAwIFIgXSANCj4+DQplbmRvYmoNCg0KNCAwIG9iag0KPDwNCi9UeXBlIC9QYWdlDQovUGFyZW50IDMgMCBSDQovUmVzb3VyY2VzIDw8DQovRm9udCA8PA0KL0YxIDkgMCBSIA0KPj4NCi9Qcm9jU2V0IDggMCBSDQo+Pg0KL01lZGlhQm94IFswIDAgNjEyLjAwMDAgNzkyLjAwMDBdDQovQ29udGVudHMgNSAwIFINCj4+DQplbmRvYmoNCg0KNSAwIG9iag0KPDwgL0xlbmd0aCAxMDc0ID4+DQpzdHJlYW0NCjIgSg0KQlQNCjAgMCAwIHJnDQovRjEgMDAyNyBUZg0KNTcuMzc1MCA3MjIuMjgwMCBUZA0KKCBBIFNpbXBsZSBQREYgRmlsZSApIFRqDQpFVA0KQlQNCi9GMSAwMDEwIFRmDQo2OS4yNTAwIDY4OC42MDgwIFRkDQooIFRoaXMgaXMgYSBzbWFsbCBkZW1vbnN0cmF0aW9uIC5wZGYgZmlsZSAtICkgVGoNCkVUDQpCVA0KL0YxIDAwMTAgVGYNCjY5LjI1MDAgNjY0LjcwNDAgVGQNCigganVzdCBmb3IgdXNlIGluIHRoZSBWaXJ0dWFsIE1lY2hhbmljcyB0dXRvcmlhbHMuIE1vcmUgdGV4dC4gQW5kIG1vcmUgKSBUag0KRVQNCkJUDQovRjEgMDAxMCBUZg0KNjkuMjUwMCA2NTIuNzUyMCBUZA0KKCB0ZXh0LiBBbmQgbW9yZSB0ZXh0LiBBbmQgbW9yZSB0ZXh0LiBBbmQgbW9yZSB0ZXh0LiApIFRqDQpFVA0KQlQNCi9GMSAwMDEwIFRmDQo2OS4yNTAwIDYyOC44NDgwIFRkDQooIEFuZCBtb3JlIHRleHQuIEFuZCBtb3JlIHRleHQuIEFuZCBtb3JlIHRleHQuIEFuZCBtb3JlIHRleHQuIEFuZCBtb3JlICkgVGoNCkVUDQpCVA0KL0YxIDAwMTAgVGYNCjY5LjI1MDAgNjE2Ljg5NjAgVGQNCiggdGV4dC4gQW5kIG1vcmUgdGV4dC4gQm9yaW5nLCB6enp6ei4gQW5kIG1vcmUgdGV4dC4gQW5kIG1vcmUgdGV4dC4gQW5kICkgVGoNCkVUDQpCVA0KL0YxIDAwMTAgVGYNCjY5LjI1MDAgNjA0Ljk0NDAgVGQNCiggbW9yZSB0ZXh0LiBBbmQgbW9yZSB0ZXh0LiBBbmQgbW9yZSB0ZXh0LiBBbmQgbW9yZSB0ZXh0LiBBbmQgbW9yZSB0ZXh0LiApIFRqDQpFVA0KQlQNCi9GMSAwMDEwIFRmDQo2OS4yNTAwIDU5Mi45OTIwIFRkDQooIEFuZCBtb3JlIHRleHQuIEFuZCBtb3JlIHRleHQuICkgVGoNCkVUDQpCVA0KL0YxIDAwMTAgVGYNCjY5LjI1MDAgNTY5LjA4ODAgVGQNCiggQW5kIG1vcmUgdGV4dC4gQW5kIG1vcmUgdGV4dC4gQW5kIG1vcmUgdGV4dC4gQW5kIG1vcmUgdGV4dC4gQW5kIG1vcmUgKSBUag0KRVQNCkJUDQovRjEgMDAxMCBUZg0KNjkuMjUwMCA1NTcuMTM2MCBUZA0KKCB0ZXh0LiBBbmQgbW9yZSB0ZXh0LiBBbmQgbW9yZSB0ZXh0LiBFdmVuIG1vcmUuIENvbnRpbnVlZCBvbiBwYWdlIDIgLi4uKSBUag0KRVQNCmVuZHN0cmVhbQ0KZW5kb2JqDQoNCjYgMCBvYmoNCjw8DQovVHlwZSAvUGFnZQ0KL1BhcmVudCAzIDAgUg0KL1Jlc291cmNlcyA8PA0KL0ZvbnQgPDwNCi9GMSA5IDAgUiANCj4+DQovUHJvY1NldCA4IDAgUg0KPj4NCi9NZWRpYUJveCBbMCAwIDYxMi4wMDAwIDc5Mi4wMDAwXQ0KL0NvbnRlbnRzIDcgMCBSDQo+Pg0KZW5kb2JqDQoNCjcgMCBvYmoNCjw8IC9MZW5ndGggNjc2ID4+DQpzdHJlYW0NCjIgSg0KQlQNCjAgMCAwIHJnDQovRjEgMDAyNyBUZg0KNTcuMzc1MCA3MjIuMjgwMCBUZA0KKCBTaW1wbGUgUERGIEZpbGUgMiApIFRqDQpFVA0KQlQNCi9GMSAwMDEwIFRmDQo2OS4yNTAwIDY4OC42MDgwIFRkDQooIC4uLmNvbnRpbnVlZCBmcm9tIHBhZ2UgMS4gWWV0IG1vcmUgdGV4dC4gQW5kIG1vcmUgdGV4dC4gQW5kIG1vcmUgdGV4dC4gKSBUag0KRVQNCkJUDQovRjEgMDAxMCBUZg0KNjkuMjUwMCA2NzYuNjU2MCBUZA0KKCBBbmQgbW9yZSB0ZXh0LiBBbmQgbW9yZSB0ZXh0LiBBbmQgbW9yZSB0ZXh0LiBBbmQgbW9yZSB0ZXh0LiBBbmQgbW9yZSApIFRqDQpFVA0KQlQNCi9GMSAwMDEwIFRmDQo2OS4yNTAwIDY2NC43MDQwIFRkDQooIHRleHQuIE9oLCBob3cgYm9yaW5nIHR5cGluZyB0aGlzIHN0dWZmLiBCdXQgbm90IGFzIGJvcmluZyBhcyB3YXRjaGluZyApIFRqDQpFVA0KQlQNCi9GMSAwMDEwIFRmDQo2OS4yNTAwIDY1Mi43NTIwIFRkDQooIHBhaW50IGRyeS4gQW5kIG1vcmUgdGV4dC4gQW5kIG1vcmUgdGV4dC4gQW5kIG1vcmUgdGV4dC4gQW5kIG1vcmUgdGV4dC4gKSBUag0KRVQNCkJUDQovRjEgMDAxMCBUZg0KNjkuMjUwMCA2NDAuODAwMCBUZA0KKCBCb3JpbmcuICBNb3JlLCBhIGxpdHRsZSBtb3JlIHRleHQuIFRoZSBlbmQsIGFuZCBqdXN0IGFzIHdlbGwuICkgVGoNCkVUDQplbmRzdHJlYW0NCmVuZG9iag0KDQo4IDAgb2JqDQpbL1BERiAvVGV4dF0NCmVuZG9iag0KDQo5IDAgb2JqDQo8PA0KL1R5cGUgL0ZvbnQNCi9TdWJ0eXBlIC9UeXBlMQ0KL05hbWUgL0YxDQovQmFzZUZvbnQgL0hlbHZldGljYQ0KL0VuY29kaW5nIC9XaW5BbnNpRW5jb2RpbmcNCj4+DQplbmRvYmoNCg0KMTAgMCBvYmoNCjw8DQovQ3JlYXRvciAoUmF2ZSBcKGh0dHA6Ly93d3cubmV2cm9uYS5jb20vcmF2ZVwpKQ0KL1Byb2R1Y2VyIChOZXZyb25hIERlc2lnbnMpDQovQ3JlYXRpb25EYXRlIChEOjIwMDYwMzAxMDcyODI2KQ0KPj4NCmVuZG9iag0KDQp4cmVmDQowIDExDQowMDAwMDAwMDAwIDY1NTM1IGYNCjAwMDAwMDAwMTkgMDAwMDAgbg0KMDAwMDAwMDA5MyAwMDAwMCBuDQowMDAwMDAwMTQ3IDAwMDAwIG4NCjAwMDAwMDAyMjIgMDAwMDAgbg0KMDAwMDAwMDM5MCAwMDAwMCBuDQowMDAwMDAxNTIyIDAwMDAwIG4NCjAwMDAwMDE2OTAgMDAwMDAgbg0KMDAwMDAwMjQyMyAwMDAwMCBuDQowMDAwMDAyNDU2IDAwMDAwIG4NCjAwMDAwMDI1NzQgMDAwMDAgbg0KDQp0cmFpbGVyDQo8PA0KL1NpemUgMTENCi9Sb290IDEgMCBSDQovSW5mbyAxMCAwIFINCj4+DQoNCnN0YXJ0eHJlZg0KMjcxNA0KJSVFT0YNCg==',
                'title' => $ttiId.Str::random(5),
            ])->object();

        if (! isset($response->versionNo)) {
            throw new TraderException(collect([
                'driver' => 'dmcc',
                'step' => 'uploadTTIDocumentAndGetVersionNumber',
                'requestBody' => [
                    'ttiDocumentName' => $ttiId.Str::random(5),
                    'ttiDocument' => 'JVBERi0xLjMNCiXi48/TDQoNCjEgMCBvYmoNCjw8DQovVHlwZSAvQ2F0YWxvZw0KL091dGxpbmVzIDIgMCBSDQovUGFnZXMgMyAwIFINCj4+DQplbmRvYmoNCg0KMiAwIG9iag0KPDwNCi9UeXBlIC9PdXRsaW5lcw0KL0NvdW50IDANCj4+DQplbmRvYmoNCg0KMyAwIG9iag0KPDwNCi9UeXBlIC9QYWdlcw0KL0NvdW50IDINCi9LaWRzIFsgNCAwIFIgNiAwIFIgXSANCj4+DQplbmRvYmoNCg0KNCAwIG9iag0KPDwNCi9UeXBlIC9QYWdlDQovUGFyZW50IDMgMCBSDQovUmVzb3VyY2VzIDw8DQovRm9udCA8PA0KL0YxIDkgMCBSIA0KPj4NCi9Qcm9jU2V0IDggMCBSDQo+Pg0KL01lZGlhQm94IFswIDAgNjEyLjAwMDAgNzkyLjAwMDBdDQovQ29udGVudHMgNSAwIFINCj4+DQplbmRvYmoNCg0KNSAwIG9iag0KPDwgL0xlbmd0aCAxMDc0ID4+DQpzdHJlYW0NCjIgSg0KQlQNCjAgMCAwIHJnDQovRjEgMDAyNyBUZg0KNTcuMzc1MCA3MjIuMjgwMCBUZA0KKCBBIFNpbXBsZSBQREYgRmlsZSApIFRqDQpFVA0KQlQNCi9GMSAwMDEwIFRmDQo2OS4yNTAwIDY4OC42MDgwIFRkDQooIFRoaXMgaXMgYSBzbWFsbCBkZW1vbnN0cmF0aW9uIC5wZGYgZmlsZSAtICkgVGoNCkVUDQpCVA0KL0YxIDAwMTAgVGYNCjY5LjI1MDAgNjY0LjcwNDAgVGQNCigganVzdCBmb3IgdXNlIGluIHRoZSBWaXJ0dWFsIE1lY2hhbmljcyB0dXRvcmlhbHMuIE1vcmUgdGV4dC4gQW5kIG1vcmUgKSBUag0KRVQNCkJUDQovRjEgMDAxMCBUZg0KNjkuMjUwMCA2NTIuNzUyMCBUZA0KKCB0ZXh0LiBBbmQgbW9yZSB0ZXh0LiBBbmQgbW9yZSB0ZXh0LiBBbmQgbW9yZSB0ZXh0LiApIFRqDQpFVA0KQlQNCi9GMSAwMDEwIFRmDQo2OS4yNTAwIDYyOC44NDgwIFRkDQooIEFuZCBtb3JlIHRleHQuIEFuZCBtb3JlIHRleHQuIEFuZCBtb3JlIHRleHQuIEFuZCBtb3JlIHRleHQuIEFuZCBtb3JlICkgVGoNCkVUDQpCVA0KL0YxIDAwMTAgVGYNCjY5LjI1MDAgNjE2Ljg5NjAgVGQNCiggdGV4dC4gQW5kIG1vcmUgdGV4dC4gQm9yaW5nLCB6enp6ei4gQW5kIG1vcmUgdGV4dC4gQW5kIG1vcmUgdGV4dC4gQW5kICkgVGoNCkVUDQpCVA0KL0YxIDAwMTAgVGYNCjY5LjI1MDAgNjA0Ljk0NDAgVGQNCiggbW9yZSB0ZXh0LiBBbmQgbW9yZSB0ZXh0LiBBbmQgbW9yZSB0ZXh0LiBBbmQgbW9yZSB0ZXh0LiBBbmQgbW9yZSB0ZXh0LiApIFRqDQpFVA0KQlQNCi9GMSAwMDEwIFRmDQo2OS4yNTAwIDU5Mi45OTIwIFRkDQooIEFuZCBtb3JlIHRleHQuIEFuZCBtb3JlIHRleHQuICkgVGoNCkVUDQpCVA0KL0YxIDAwMTAgVGYNCjY5LjI1MDAgNTY5LjA4ODAgVGQNCiggQW5kIG1vcmUgdGV4dC4gQW5kIG1vcmUgdGV4dC4gQW5kIG1vcmUgdGV4dC4gQW5kIG1vcmUgdGV4dC4gQW5kIG1vcmUgKSBUag0KRVQNCkJUDQovRjEgMDAxMCBUZg0KNjkuMjUwMCA1NTcuMTM2MCBUZA0KKCB0ZXh0LiBBbmQgbW9yZSB0ZXh0LiBBbmQgbW9yZSB0ZXh0LiBFdmVuIG1vcmUuIENvbnRpbnVlZCBvbiBwYWdlIDIgLi4uKSBUag0KRVQNCmVuZHN0cmVhbQ0KZW5kb2JqDQoNCjYgMCBvYmoNCjw8DQovVHlwZSAvUGFnZQ0KL1BhcmVudCAzIDAgUg0KL1Jlc291cmNlcyA8PA0KL0ZvbnQgPDwNCi9GMSA5IDAgUiANCj4+DQovUHJvY1NldCA4IDAgUg0KPj4NCi9NZWRpYUJveCBbMCAwIDYxMi4wMDAwIDc5Mi4wMDAwXQ0KL0NvbnRlbnRzIDcgMCBSDQo+Pg0KZW5kb2JqDQoNCjcgMCBvYmoNCjw8IC9MZW5ndGggNjc2ID4+DQpzdHJlYW0NCjIgSg0KQlQNCjAgMCAwIHJnDQovRjEgMDAyNyBUZg0KNTcuMzc1MCA3MjIuMjgwMCBUZA0KKCBTaW1wbGUgUERGIEZpbGUgMiApIFRqDQpFVA0KQlQNCi9GMSAwMDEwIFRmDQo2OS4yNTAwIDY4OC42MDgwIFRkDQooIC4uLmNvbnRpbnVlZCBmcm9tIHBhZ2UgMS4gWWV0IG1vcmUgdGV4dC4gQW5kIG1vcmUgdGV4dC4gQW5kIG1vcmUgdGV4dC4gKSBUag0KRVQNCkJUDQovRjEgMDAxMCBUZg0KNjkuMjUwMCA2NzYuNjU2MCBUZA0KKCBBbmQgbW9yZSB0ZXh0LiBBbmQgbW9yZSB0ZXh0LiBBbmQgbW9yZSB0ZXh0LiBBbmQgbW9yZSB0ZXh0LiBBbmQgbW9yZSApIFRqDQpFVA0KQlQNCi9GMSAwMDEwIFRmDQo2OS4yNTAwIDY2NC43MDQwIFRkDQooIHRleHQuIE9oLCBob3cgYm9yaW5nIHR5cGluZyB0aGlzIHN0dWZmLiBCdXQgbm90IGFzIGJvcmluZyBhcyB3YXRjaGluZyApIFRqDQpFVA0KQlQNCi9GMSAwMDEwIFRmDQo2OS4yNTAwIDY1Mi43NTIwIFRkDQooIHBhaW50IGRyeS4gQW5kIG1vcmUgdGV4dC4gQW5kIG1vcmUgdGV4dC4gQW5kIG1vcmUgdGV4dC4gQW5kIG1vcmUgdGV4dC4gKSBUag0KRVQNCkJUDQovRjEgMDAxMCBUZg0KNjkuMjUwMCA2NDAuODAwMCBUZA0KKCBCb3JpbmcuICBNb3JlLCBhIGxpdHRsZSBtb3JlIHRleHQuIFRoZSBlbmQsIGFuZCBqdXN0IGFzIHdlbGwuICkgVGoNCkVUDQplbmRzdHJlYW0NCmVuZG9iag0KDQo4IDAgb2JqDQpbL1BERiAvVGV4dF0NCmVuZG9iag0KDQo5IDAgb2JqDQo8PA0KL1R5cGUgL0ZvbnQNCi9TdWJ0eXBlIC9UeXBlMQ0KL05hbWUgL0YxDQovQmFzZUZvbnQgL0hlbHZldGljYQ0KL0VuY29kaW5nIC9XaW5BbnNpRW5jb2RpbmcNCj4+DQplbmRvYmoNCg0KMTAgMCBvYmoNCjw8DQovQ3JlYXRvciAoUmF2ZSBcKGh0dHA6Ly93d3cubmV2cm9uYS5jb20vcmF2ZVwpKQ0KL1Byb2R1Y2VyIChOZXZyb25hIERlc2lnbnMpDQovQ3JlYXRpb25EYXRlIChEOjIwMDYwMzAxMDcyODI2KQ0KPj4NCmVuZG9iag0KDQp4cmVmDQowIDExDQowMDAwMDAwMDAwIDY1NTM1IGYNCjAwMDAwMDAwMTkgMDAwMDAgbg0KMDAwMDAwMDA5MyAwMDAwMCBuDQowMDAwMDAwMTQ3IDAwMDAwIG4NCjAwMDAwMDAyMjIgMDAwMDAgbg0KMDAwMDAwMDM5MCAwMDAwMCBuDQowMDAwMDAxNTIyIDAwMDAwIG4NCjAwMDAwMDE2OTAgMDAwMDAgbg0KMDAwMDAwMjQyMyAwMDAwMCBuDQowMDAwMDAyNDU2IDAwMDAwIG4NCjAwMDAwMDI1NzQgMDAwMDAgbg0KDQp0cmFpbGVyDQo8PA0KL1NpemUgMTENCi9Sb290IDEgMCBSDQovSW5mbyAxMCAwIFINCj4+DQoNCnN0YXJ0eHJlZg0KMjcxNA0KJSVFT0YNCg==',
                    'title' => $ttiId.Str::random(5),
                ],
                'responseBody' => $response->body(),
            ]));
        }

        return $response->versionNo;
    }

    /**
     * @throws TraderException
     */
    public function issueMurabahaPurchaseOffer(string $ttiId, string $versionNo): void
    {
        $response = $this->soap
            ->baseWsdl($this->prefixUrl('issueMurabahaPurchaseOffer'))
            ->call('issueMurabahaPurchaseOffer', [
                'ttiId' => $ttiId,
                'comments' => 'create MPO',
                'ttiDocumentVersionNo' => $versionNo,
            ]);

        if (! $this->isSuccess($response)) {
            throw new TraderException(collect([
                'driver' => 'dmcc',
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
