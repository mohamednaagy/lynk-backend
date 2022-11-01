<?php

namespace App\Support\Traders\Drivers;

use App\Enums\FinancingOrderStatus;
use App\Models\FinancingOrder;
use App\Models\TraderHistory;
use App\Models\TraderOrder;
use App\Support\PdfGenerator\PdfGenerator;
use App\Support\Traders\Contracts\TraderInterface;
use CodeDredd\Soap\Client\Response;
use CodeDredd\Soap\Facades\Soap;
use CodeDredd\Soap\SoapClient;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class DmccDriver implements TraderInterface
{
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

    public function getTTI(FinancingOrder $financingOrder): object
    {
        // create TTIID
        $response = $this->soap
            ->baseWsdl($this->prefixUrl('getTTIIDForIssuePTP'))
            ->call('getTTIIDForIssuePTP', [
                'currency' => 'SAR',
                'costPrice' => $financingOrder->amount,
                'profit' => $financingOrder->selling_price - $financingOrder->amount,
                'paymentTerms' => config('dmcc.tti.payment_terms'),
                'unitOfDuration' => config('dmcc.tti.unit_of_duration'),
                'product' => null,
                'registeredMember' => 'BOLFT',
                'client' => null,
            ]);

        $ttiId = $response->object()->ttiId;

        // store in trader order
        $financingOrder->traderOrder()->create([
            'order_id' => $financingOrder->id,
            'provider' => 'DMCC',
            'type' => 'TTIID',
            'reference' => $ttiId,
        ]);

        return $ttiId;
    }

    public function respondPTP(string $ttiId)
    {
        $response = $this->soap
            ->baseWsdl($this->prefixUrl('respondPTPService'))
            ->call('respondPTPService', [
                'ttiId' => $ttiId,
                'comments' => 'create PTP',
                'submitAction' => 'true',
            ]);

        if ($this->isSuccess($response)) {
            // generate internal doc - selling commodity to customer
            $html = view('selling-commodity-to-customer')->render();
            PdfGenerator::outputFromHtml($html, 'test.pdf', [
                'gotoOptions' => ['waitUntil' => 'networkidle0'],
            ]);

            $traderOrder = TraderOrder::query()
                ->where('type', 'TTIID')
                ->where('reference', $ttiId)
                ->first();

            $traderOrder->order->update(['status' => FinancingOrderStatus::CommodityPurchased]);

            // request PTP document
            $response = $this->soap
                ->baseWsdl($this->prefixUrl('getDocumentByTypeAndTransaction'))
                ->call('getDocumentByTypeAndTransaction', [
                    'ttiId' => $ttiId,
                    'documentType' => 'Promise to Purchase',
                ]);

            // generate internal doc - transfer ownership to lender
            $html = view('transfer-ownership-to-lender')->render();
            PdfGenerator::outputFromHtml($html, 'test.pdf', [
                'gotoOptions' => ['waitUntil' => 'networkidle0'],
            ]);

            $traderOrder->order->update(['status' => FinancingOrderStatus::SellingCommodityToCustomer]);

            // store in PTP path with ttiId filename
            Storage::put(
                $traderOrder->order_id.'/PTP/'.$ttiId.'.pdf',
                base64_decode($response->object()->getdocument[0]->getDocumentByTypeResponse[0]->document)
            );

            if ($traderOrder) {
                TraderHistory::query()->create([
                    'trader_order_id' => $traderOrder->id,
                    'action' => 'response PTP and store document',
                ]);
            }
        }
    }

    public function issueMurabaha(string $ttiId)
    {
        // upload TTIDocument for now it sample PDF to get version
        $response = $this->soap
            ->baseWsdl($this->prefixUrl('uploadTTIDocument'))
            ->call('uploadTTIDocument', [
                'ttiDocumentName' => $ttiId,
                'ttiDocument' => 'JVBERi0xLjMNCiXi48/TDQoNCjEgMCBvYmoNCjw8DQovVHlwZSAvQ2F0YWxvZw0KL091dGxpbmVzIDIgMCBSDQovUGFnZXMgMyAwIFINCj4+DQplbmRvYmoNCg0KMiAwIG9iag0KPDwNCi9UeXBlIC9PdXRsaW5lcw0KL0NvdW50IDANCj4+DQplbmRvYmoNCg0KMyAwIG9iag0KPDwNCi9UeXBlIC9QYWdlcw0KL0NvdW50IDINCi9LaWRzIFsgNCAwIFIgNiAwIFIgXSANCj4+DQplbmRvYmoNCg0KNCAwIG9iag0KPDwNCi9UeXBlIC9QYWdlDQovUGFyZW50IDMgMCBSDQovUmVzb3VyY2VzIDw8DQovRm9udCA8PA0KL0YxIDkgMCBSIA0KPj4NCi9Qcm9jU2V0IDggMCBSDQo+Pg0KL01lZGlhQm94IFswIDAgNjEyLjAwMDAgNzkyLjAwMDBdDQovQ29udGVudHMgNSAwIFINCj4+DQplbmRvYmoNCg0KNSAwIG9iag0KPDwgL0xlbmd0aCAxMDc0ID4+DQpzdHJlYW0NCjIgSg0KQlQNCjAgMCAwIHJnDQovRjEgMDAyNyBUZg0KNTcuMzc1MCA3MjIuMjgwMCBUZA0KKCBBIFNpbXBsZSBQREYgRmlsZSApIFRqDQpFVA0KQlQNCi9GMSAwMDEwIFRmDQo2OS4yNTAwIDY4OC42MDgwIFRkDQooIFRoaXMgaXMgYSBzbWFsbCBkZW1vbnN0cmF0aW9uIC5wZGYgZmlsZSAtICkgVGoNCkVUDQpCVA0KL0YxIDAwMTAgVGYNCjY5LjI1MDAgNjY0LjcwNDAgVGQNCigganVzdCBmb3IgdXNlIGluIHRoZSBWaXJ0dWFsIE1lY2hhbmljcyB0dXRvcmlhbHMuIE1vcmUgdGV4dC4gQW5kIG1vcmUgKSBUag0KRVQNCkJUDQovRjEgMDAxMCBUZg0KNjkuMjUwMCA2NTIuNzUyMCBUZA0KKCB0ZXh0LiBBbmQgbW9yZSB0ZXh0LiBBbmQgbW9yZSB0ZXh0LiBBbmQgbW9yZSB0ZXh0LiApIFRqDQpFVA0KQlQNCi9GMSAwMDEwIFRmDQo2OS4yNTAwIDYyOC44NDgwIFRkDQooIEFuZCBtb3JlIHRleHQuIEFuZCBtb3JlIHRleHQuIEFuZCBtb3JlIHRleHQuIEFuZCBtb3JlIHRleHQuIEFuZCBtb3JlICkgVGoNCkVUDQpCVA0KL0YxIDAwMTAgVGYNCjY5LjI1MDAgNjE2Ljg5NjAgVGQNCiggdGV4dC4gQW5kIG1vcmUgdGV4dC4gQm9yaW5nLCB6enp6ei4gQW5kIG1vcmUgdGV4dC4gQW5kIG1vcmUgdGV4dC4gQW5kICkgVGoNCkVUDQpCVA0KL0YxIDAwMTAgVGYNCjY5LjI1MDAgNjA0Ljk0NDAgVGQNCiggbW9yZSB0ZXh0LiBBbmQgbW9yZSB0ZXh0LiBBbmQgbW9yZSB0ZXh0LiBBbmQgbW9yZSB0ZXh0LiBBbmQgbW9yZSB0ZXh0LiApIFRqDQpFVA0KQlQNCi9GMSAwMDEwIFRmDQo2OS4yNTAwIDU5Mi45OTIwIFRkDQooIEFuZCBtb3JlIHRleHQuIEFuZCBtb3JlIHRleHQuICkgVGoNCkVUDQpCVA0KL0YxIDAwMTAgVGYNCjY5LjI1MDAgNTY5LjA4ODAgVGQNCiggQW5kIG1vcmUgdGV4dC4gQW5kIG1vcmUgdGV4dC4gQW5kIG1vcmUgdGV4dC4gQW5kIG1vcmUgdGV4dC4gQW5kIG1vcmUgKSBUag0KRVQNCkJUDQovRjEgMDAxMCBUZg0KNjkuMjUwMCA1NTcuMTM2MCBUZA0KKCB0ZXh0LiBBbmQgbW9yZSB0ZXh0LiBBbmQgbW9yZSB0ZXh0LiBFdmVuIG1vcmUuIENvbnRpbnVlZCBvbiBwYWdlIDIgLi4uKSBUag0KRVQNCmVuZHN0cmVhbQ0KZW5kb2JqDQoNCjYgMCBvYmoNCjw8DQovVHlwZSAvUGFnZQ0KL1BhcmVudCAzIDAgUg0KL1Jlc291cmNlcyA8PA0KL0ZvbnQgPDwNCi9GMSA5IDAgUiANCj4+DQovUHJvY1NldCA4IDAgUg0KPj4NCi9NZWRpYUJveCBbMCAwIDYxMi4wMDAwIDc5Mi4wMDAwXQ0KL0NvbnRlbnRzIDcgMCBSDQo+Pg0KZW5kb2JqDQoNCjcgMCBvYmoNCjw8IC9MZW5ndGggNjc2ID4+DQpzdHJlYW0NCjIgSg0KQlQNCjAgMCAwIHJnDQovRjEgMDAyNyBUZg0KNTcuMzc1MCA3MjIuMjgwMCBUZA0KKCBTaW1wbGUgUERGIEZpbGUgMiApIFRqDQpFVA0KQlQNCi9GMSAwMDEwIFRmDQo2OS4yNTAwIDY4OC42MDgwIFRkDQooIC4uLmNvbnRpbnVlZCBmcm9tIHBhZ2UgMS4gWWV0IG1vcmUgdGV4dC4gQW5kIG1vcmUgdGV4dC4gQW5kIG1vcmUgdGV4dC4gKSBUag0KRVQNCkJUDQovRjEgMDAxMCBUZg0KNjkuMjUwMCA2NzYuNjU2MCBUZA0KKCBBbmQgbW9yZSB0ZXh0LiBBbmQgbW9yZSB0ZXh0LiBBbmQgbW9yZSB0ZXh0LiBBbmQgbW9yZSB0ZXh0LiBBbmQgbW9yZSApIFRqDQpFVA0KQlQNCi9GMSAwMDEwIFRmDQo2OS4yNTAwIDY2NC43MDQwIFRkDQooIHRleHQuIE9oLCBob3cgYm9yaW5nIHR5cGluZyB0aGlzIHN0dWZmLiBCdXQgbm90IGFzIGJvcmluZyBhcyB3YXRjaGluZyApIFRqDQpFVA0KQlQNCi9GMSAwMDEwIFRmDQo2OS4yNTAwIDY1Mi43NTIwIFRkDQooIHBhaW50IGRyeS4gQW5kIG1vcmUgdGV4dC4gQW5kIG1vcmUgdGV4dC4gQW5kIG1vcmUgdGV4dC4gQW5kIG1vcmUgdGV4dC4gKSBUag0KRVQNCkJUDQovRjEgMDAxMCBUZg0KNjkuMjUwMCA2NDAuODAwMCBUZA0KKCBCb3JpbmcuICBNb3JlLCBhIGxpdHRsZSBtb3JlIHRleHQuIFRoZSBlbmQsIGFuZCBqdXN0IGFzIHdlbGwuICkgVGoNCkVUDQplbmRzdHJlYW0NCmVuZG9iag0KDQo4IDAgb2JqDQpbL1BERiAvVGV4dF0NCmVuZG9iag0KDQo5IDAgb2JqDQo8PA0KL1R5cGUgL0ZvbnQNCi9TdWJ0eXBlIC9UeXBlMQ0KL05hbWUgL0YxDQovQmFzZUZvbnQgL0hlbHZldGljYQ0KL0VuY29kaW5nIC9XaW5BbnNpRW5jb2RpbmcNCj4+DQplbmRvYmoNCg0KMTAgMCBvYmoNCjw8DQovQ3JlYXRvciAoUmF2ZSBcKGh0dHA6Ly93d3cubmV2cm9uYS5jb20vcmF2ZVwpKQ0KL1Byb2R1Y2VyIChOZXZyb25hIERlc2lnbnMpDQovQ3JlYXRpb25EYXRlIChEOjIwMDYwMzAxMDcyODI2KQ0KPj4NCmVuZG9iag0KDQp4cmVmDQowIDExDQowMDAwMDAwMDAwIDY1NTM1IGYNCjAwMDAwMDAwMTkgMDAwMDAgbg0KMDAwMDAwMDA5MyAwMDAwMCBuDQowMDAwMDAwMTQ3IDAwMDAwIG4NCjAwMDAwMDAyMjIgMDAwMDAgbg0KMDAwMDAwMDM5MCAwMDAwMCBuDQowMDAwMDAxNTIyIDAwMDAwIG4NCjAwMDAwMDE2OTAgMDAwMDAgbg0KMDAwMDAwMjQyMyAwMDAwMCBuDQowMDAwMDAyNDU2IDAwMDAwIG4NCjAwMDAwMDI1NzQgMDAwMDAgbg0KDQp0cmFpbGVyDQo8PA0KL1NpemUgMTENCi9Sb290IDEgMCBSDQovSW5mbyAxMCAwIFINCj4+DQoNCnN0YXJ0eHJlZg0KMjcxNA0KJSVFT0YNCg==',
                'title' => $ttiId,
            ]);

        if (isset($response->object()->versionNo)) {
            // request MPO
            $response = $this->soap
                ->baseWsdl($this->prefixUrl('issueMurabahaPurchaseOffer'))
                ->call('issueMurabahaPurchaseOffer', [
                    'ttiId' => $ttiId,
                    'comments' => 'create MPO',
                    'ttiDocumentVersionNo' => $response->object()->versionNo,
                ]);

            if ($this->isSuccess($response)) {
                // request MPO document
                $response = $this->soap
                    ->baseWsdl($this->prefixUrl('getDocumentByTypeAndTransaction'))
                    ->call('getDocumentByTypeAndTransaction', [
                        'ttiId' => $ttiId,
                        'documentType' => 'Murabaha Purchase Offer Document',
                    ]);

                $traderOrder = TraderOrder::query()
                    ->where('type', 'TTIID')
                    ->where('reference', $ttiId)
                    ->first();

                $traderOrder->order->update(['status' => FinancingOrderStatus::IssueMurabahaOffer]);

                // store in PTP path with ttiId filename
                Storage::put(
                    $traderOrder->order_id.'/MPO/'.$ttiId.'.pdf',
                    base64_decode($response->object()->getdocument[0]->getDocumentByTypeResponse[0]->document)
                );

                if ($traderOrder) {
                    TraderHistory::query()->create([
                        'trader_order_id' => $traderOrder->id,
                        'action' => 'issue MPO and store document',
                    ]);
                }
            }
        }
    }

    public function fetchNotification(): ?array
    {
        $response = $this->soap
            ->baseWsdl($this->prefixUrl('notificationDetailsRequest'))
            ->call('notificationDetailsRequest', [
                'notificationType' => 'ACTIONABLE',
            ]);

        if ($this->isSuccess($response)) {
            return $response->json();
        }
        throw new RuntimeException();
    }

    public function fetchMurabahaNotification(): ?array
    {
        $response = $this->soap
            ->baseWsdl($this->prefixUrl('notificationDetailsRequest'))
            ->call('notificationDetailsRequest', [
                'notificationType' => 'FYI',
            ]);

        if ($this->isSuccess($response)) {
            return $response->json();
        }
        throw new RuntimeException();
    }

    public function completeOrder(string $ttiId)
    {
        $traderOrder = TraderOrder::query()
            ->where('type', 'TTIID')
            ->where('reference', $ttiId)
            ->first();

        $traderOrder->order->update(['status' => FinancingOrderStatus::MurabahaSaleCompleted]);
    }

    private function prefixUrl($url): string
    {
        return 'https://'.config('dmcc.username').':'.config('dmcc.password').'@na2.ai.dm-us.informaticacloud.com/active-bpel/soap/'.$url.'?wsdl';
    }

    private function isSuccess(Response $response): bool
    {
        return $response->successful() && $response->json()['successCode'] === '0000';
    }
}
