<?php

namespace App\Support\Traders\Drivers\Dmcc\Strategies;

use App\Enums\FinancingOrderHistory;
use App\Enums\FinancingOrderStatus;
use App\Enums\MediaCollections\TraderOrderMediaCollection;
use App\Enums\TraderOrderCancelReason;
use App\Enums\TraderOrderCancelType;
use App\Enums\TraderOrderMode;
use App\Enums\TraderOrderStatus;
use App\Exceptions\TraderException;
use App\Jobs\General\ProcessAskClientForWakala;
use App\Jobs\General\ProcessProceedContractAndClientWakala;
use App\Models\FinancingOrder;
use App\Models\TraderOrder;
use App\Models\User;
use App\Support\Traders\Contracts\TraderInterface;
use App\Support\Traders\Drivers\Dmcc\Jobs\V1\ProcessDmccMpoOrder;
use App\Support\Traders\Drivers\Dmcc\Jobs\V1\ProcessDmccRespondedToPtpOrder;
use App\Support\Traders\Drivers\Dmcc\Jobs\V1\ProcessDmccSellingCommodityToCustomerOrder;
use App\Support\Traders\Traits\TraderHelperTrait;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use CodeDredd\Soap\Client\Response;
use CodeDredd\Soap\Facades\Soap;
use CodeDredd\Soap\SoapClient;
use Exception;
use Illuminate\Support\Str;

class DmccV1Driver implements TraderInterface
{
    use TraderHelperTrait {
        createTraderOrder as traitCreateTraderOrder;
    }

    protected $provider = 'dmcc';

    protected $version = 'v1';

    const notCancellableActions = [
        FinancingOrderHistory::GetMurabahaPurchaseOfferDocument,
        FinancingOrderHistory::AttachMpoDocument,
        FinancingOrderHistory::IssueMurabahaOffer,
        FinancingOrderHistory::MurabahaSaleCompleted,
        FinancingOrderHistory::GetWarrantAmendmentExceptWarrantNoDocument,
        FinancingOrderHistory::ContractSigned,
        FinancingOrderHistory::AttachWarrantAmendmentExceptWarrantNoDocument,
        FinancingOrderHistory::OrderCancelled,
    ];

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
            throw new TraderException(
                'Failed to accept agreement',
                [
                    'provider' => $this->provider,
                    'version' => $this->version,
                    'provider_response_body' => $error,
                ]
            );
        }

        $response = $this->soap
            ->baseWsdl($this->prefixUrl('acceptRejectClickThroughAgreement'))
            ->call('acceptRejectClickThroughAgreement', [
                'acceptReject' => 'true',
            ]);

        return $this->isSuccess($response);
    }

    /**
     * @throws TraderException
     */
    public function createTraderOrder(FinancingOrder $financingOrder): TraderOrder
    {
        $ttiId = $this->getTtiId($financingOrder);

        if (blank($ttiId)) {
            throw new TraderException(
                'Failed to create trader order',
                [
                    'provider' => $this->provider,
                    'version' => $this->version,
                    'financing_order_id' => $financingOrder->id,
                ]
            );
        }

        $traderOrder = $this->traitCreateTraderOrder($financingOrder, $ttiId, 'dmcc');
        $this->createTraderOrderHistory($traderOrder, FinancingOrderHistory::GetTtiId);

        return $traderOrder;
    }

    public function getDefaultInitialTradeOrderStatus()
    {
        return TraderOrderStatus::InProgress;
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

        if (! $response->successful() || blank($response->object()->NotificationAllDetailsResponse)) {
            throw new TraderException(
                'Failed to fetch notifications',
                [
                    'provider' => $this->provider,
                    'version' => $this->version,
                    'notification_type' => $type,
                    'provider_response_body' => $response->body(),
                ]
            );
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
            ->call('processNotification', $requestBody = [
                'notificationId' => $notificationId,
            ]);

        if (! $response->successful()) {
            throw new TraderException(
                'Failed to process notifications',
                [
                    'provider' => $this->provider,
                    'version' => $this->version,
                    'provider_request_body' => $requestBody,
                    'provider_response_body' => $response->body(),
                ]
            );
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
        $response = $this->soap
            ->baseWsdl($this->prefixUrl('getTTIIDForIssuePTP'))
            ->call('getTTIIDForIssuePTP', $requestBody = [
                'currency' => $financingOrder->currency,
                'costPrice' => $financingOrder->amount->convertAndFormatByDecimal(),
                'profit' => $financingOrder->selling_price->subtract($financingOrder->amount)->convertAndFormatByDecimal(),
                'paymentTerms' => config('trader.providers.dmcc.tti.payment_terms'),
                'unitOfDuration' => config('trader.providers.dmcc.tti.unit_of_duration'),
                'product' => null,
                'registeredMember' => config('trader.providers.dmcc.tti.registered_member'),
                'client' => null,
            ])->object();

        if (! isset($response->ttiId) || blank($response->ttiId)) {
            throw new TraderException(
                'Failed to get TTID',
                [
                    'provider' => $this->provider,
                    'version' => $this->version,
                    'financing_order_id' => $financingOrder->id,
                    'provider_request_body' => $requestBody,
                    'provider_response_body' => $response,
                ]
            );
        }

        return $response->ttiId;
    }

    /**
     * @throws TraderException
     */
    public function cancelOrder(FinancingOrder $financingOrder): object
    {
        $traderOrder = $financingOrder->activeTraderOrder()->first();
        $response = $this->soap
            ->baseWsdl($this->prefixUrl('cancelTTI'))
            ->call('cancelTTI', $requestBody = [
                'ttiId' => $traderOrder->reference,
                'comments' => 'Cancel Order',
                'confirmAction' => 'true',
            ]);

        if (! $this->isSuccess($response)) {
            throw new TraderException(
                'Failed to get cancel order',
                [
                    'provider' => $this->provider,
                    'version' => $this->version,
                    'trader_order_id' => $traderOrder->id,
                    'provider_request_body' => $requestBody,
                    'provider_response_body' => $response->body(),
                ]
            );
        }

        return $response->object();
    }

    /**
     * @throws TraderException
     */
    public function cancelTraderOrder(
        TraderOrder $traderOrder,
        int $cancelReason = TraderOrderCancelReason::TraderOrderIsCancelled,
        $cancelledByType = TraderOrderCancelType::System,
        ?User $cancelledBy = null
    ): object {
        $response = $this->soap
            ->baseWsdl($this->prefixUrl('cancelTTI'))
            ->call('cancelTTI', $requestBody = [
                'ttiId' => $traderOrder->reference,
                'comments' => 'Cancel Order',
                'confirmAction' => 'true',
            ]);

        if (! $this->isSuccess($response)) {
            throw new TraderException(
                'Failed to get cancel order',
                [
                    'provider' => $this->provider,
                    'version' => $this->version,
                    'trader_order_id' => $traderOrder->id,
                    'provider_request_body' => $requestBody,
                    'provider_response_body' => $response->body(),
                ]
            );
        }

        return $response->object();
    }

    /**
     * @throws TraderException
     */
    public function respondPtpService(string $ttiId): object
    {
        $response = $this->soap
            ->baseWsdl($this->prefixUrl('respondPTPService'))
            ->call('respondPTPService', $requestBody = [
                'ttiId' => $ttiId,
                'comments' => 'create PTP',
                'submitAction' => 'true',
            ]);

        if (! $this->isSuccess($response)) {
            throw new TraderException(
                'Failed to respond to PTP service',
                [
                    'provider' => $this->provider,
                    'version' => $this->version,
                    'tti_id' => $ttiId,
                    'provider_request_body' => $requestBody,
                    'provider_response_body' => $response->body(),
                ]
            );
        }

        return $response->object();
    }

    /**
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

            $data['created_at'] = $dateTime->clone();
            $separator = ' و ';
            $products = collect($traderOrder->products);
            $amount = $traderOrder->order->selling_price->convertAndFormatByDecimal(sperator: ',');
            $customerName = $traderOrder->order->customer_name;
            $productName = $products->pluck('product')->implode($separator);

            $this->storeOrderDocumentAsPdf(
                'selling-commodity-to-customer',
                [
                    'reference_number' => $traderOrder->id,
                    'company_name' => $traderOrder->order->company()->withTrashed()->first()->name,
                    'order_number' => $traderOrder->financing_order_id,
                    'products' => $this->transformProductsToCommodityProductsDTO($traderOrder->products),
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
            throw new TraderException(
                'Failed to create customer ownership document',
                [
                    'provider' => $traderOrder->provider,
                    'version' => $traderOrder->version,
                ],
                $exception
            );
        }
    }

    /**
     * @throws TraderException
     */
    public function getDocumentByTypeAndTransaction(string $ttiId, string $documentType): mixed
    {
        // request PTP document
        $response = $this->soap
            ->baseWsdl($this->prefixUrl('getDocumentByTypeAndTransaction'))
            ->call('getDocumentByTypeAndTransaction', $requestBody = [
                'ttiId' => $ttiId,
                'documentType' => $documentType,
            ]);

        if (! isset($response->object()->getdocument[0]->getDocumentByTypeResponse[0]->document)) {
            throw new TraderException(
                'Failed to get document by type and transaction',
                [
                    'provider' => $this->provider,
                    'version' => $this->version,
                    'provider_request_body' => $requestBody,
                    'provider_response_body' => $response->body(),
                ]
            );
        }

        return $response->object()->getdocument[0]->getDocumentByTypeResponse[0]->document;
    }

    /**
     * @throws TraderException
     */
    public function createTransferOwnershipToLenderDocument($traderOrder): void
    {
        try {
            $separator = ' و ';
            $products = collect($traderOrder->products);
            $amount = $traderOrder->order->amount->convertAndFormatByDecimal(sperator: ',');
            $previousOwner = $products->pluck('previous_owner')->implode($separator);
            $productName = $products->pluck('product')->implode($separator);
            $date = CarbonImmutable::now();
            $data['created_at'] = $date->clone();

            $this->storeOrderDocumentAsPdf(
                'transfer-ownership-to-lender',
                [
                    'order_id' => $traderOrder->order->id,
                    'products' => $this->transformProductsToCommodityProductsDTO($traderOrder->products),
                    'reference_number' => $traderOrder->id,
                    'company_name' => $traderOrder->order->company()->withTrashed()->first()->name,
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
            throw new TraderException(
                'Failed to create lender ownership certificate',
                [
                    'provider' => $traderOrder->provider,
                    'version' => $traderOrder->version,
                ],
                $exception
            );
        }
    }

    /**
     * @throws TraderException
     */
    public function getInventoryBasket(TraderOrder $traderOrder): object
    {
        $response = $this->soap
            ->baseWsdl($this->prefixUrl('getInventoryBasket'))
            ->call('getInventoryBasket', $requestBody = [
                'ttiId' => $traderOrder->reference,
            ]);

        if ($response->object()->errorCode != '') {
            throw new TraderException(
                'Failed to get inventory basket',
                [
                    'provider' => $traderOrder->provider,
                    'version' => $traderOrder->version,
                    'provider_request_body' => $requestBody,
                    'provider_response_body' => $response->body(),
                ]
            );
        }

        $inventoryDetails = $response->object()->inventoryDetails;
        $data = [];

        foreach ($inventoryDetails as $product) {
            $data[] = [
                'product' => $product->hsCodeDescription,
                'quantity' => $product->quantity,
                'amount' => $product->totalValue,
                'currency' => $product->currency,
                'warehouse' => $product->warehouseOrVaultId,
                'owner' => $product->owner,
                'previous_owner' => $product->previousOwner,
                'new_owner' => $product->newOwner ?? null,
                'date_time_of_purchasing_commodity' => Carbon::createFromFormat(
                    'd/m/Y H:i A',
                    $product->dateTimeOfPurchasingCommodity
                )
                    ->format('Y-m-d H:i:s'),
                'warehouse_or_vault_emirates' => $product->warehouseOrVaultEmirates,
                'warehouse_or_vault_country' => $product->warehouseOrVaultCountry,
                'inventory_record_id' => $product->inventoryRecordId,
                'warrant_percentage' => $product->warrantPercentage,
                'uom' => $product->uom,
            ];
        }

        $products['products'] = $data;
        $products['exchange_rate'] = $response->object()->exchangeRate;

        $traderOrder->update($products);

        return $response->object();
    }

    /**
     * @throws TraderException
     */
    public function uploadTTIDocumentAndGetVersionNumber(string $ttiId): mixed
    {
        // upload TTIDocument for now it sample PDF to get version
        $response = $this->soap
            ->baseWsdl($this->prefixUrl('uploadTTIDocument'))
            ->call('uploadTTIDocument', $requestBody = [
                'ttiDocumentName' => $ttiId.Str::random(5),
                'ttiDocument' => 'JVBERi0xLjMNCiXi48/TDQoNCjEgMCBvYmoNCjw8DQovVHlwZSAvQ2F0YWxvZw0KL091dGxpbmVzIDIgMCBSDQovUGFnZXMgMyAwIFINCj4+DQplbmRvYmoNCg0KMiAwIG9iag0KPDwNCi9UeXBlIC9PdXRsaW5lcw0KL0NvdW50IDANCj4+DQplbmRvYmoNCg0KMyAwIG9iag0KPDwNCi9UeXBlIC9QYWdlcw0KL0NvdW50IDINCi9LaWRzIFsgNCAwIFIgNiAwIFIgXSANCj4+DQplbmRvYmoNCg0KNCAwIG9iag0KPDwNCi9UeXBlIC9QYWdlDQovUGFyZW50IDMgMCBSDQovUmVzb3VyY2VzIDw8DQovRm9udCA8PA0KL0YxIDkgMCBSIA0KPj4NCi9Qcm9jU2V0IDggMCBSDQo+Pg0KL01lZGlhQm94IFswIDAgNjEyLjAwMDAgNzkyLjAwMDBdDQovQ29udGVudHMgNSAwIFINCj4+DQplbmRvYmoNCg0KNSAwIG9iag0KPDwgL0xlbmd0aCAxMDc0ID4+DQpzdHJlYW0NCjIgSg0KQlQNCjAgMCAwIHJnDQovRjEgMDAyNyBUZg0KNTcuMzc1MCA3MjIuMjgwMCBUZA0KKCBBIFNpbXBsZSBQREYgRmlsZSApIFRqDQpFVA0KQlQNCi9GMSAwMDEwIFRmDQo2OS4yNTAwIDY4OC42MDgwIFRkDQooIFRoaXMgaXMgYSBzbWFsbCBkZW1vbnN0cmF0aW9uIC5wZGYgZmlsZSAtICkgVGoNCkVUDQpCVA0KL0YxIDAwMTAgVGYNCjY5LjI1MDAgNjY0LjcwNDAgVGQNCigganVzdCBmb3IgdXNlIGluIHRoZSBWaXJ0dWFsIE1lY2hhbmljcyB0dXRvcmlhbHMuIE1vcmUgdGV4dC4gQW5kIG1vcmUgKSBUag0KRVQNCkJUDQovRjEgMDAxMCBUZg0KNjkuMjUwMCA2NTIuNzUyMCBUZA0KKCB0ZXh0LiBBbmQgbW9yZSB0ZXh0LiBBbmQgbW9yZSB0ZXh0LiBBbmQgbW9yZSB0ZXh0LiApIFRqDQpFVA0KQlQNCi9GMSAwMDEwIFRmDQo2OS4yNTAwIDYyOC44NDgwIFRkDQooIEFuZCBtb3JlIHRleHQuIEFuZCBtb3JlIHRleHQuIEFuZCBtb3JlIHRleHQuIEFuZCBtb3JlIHRleHQuIEFuZCBtb3JlICkgVGoNCkVUDQpCVA0KL0YxIDAwMTAgVGYNCjY5LjI1MDAgNjE2Ljg5NjAgVGQNCiggdGV4dC4gQW5kIG1vcmUgdGV4dC4gQm9yaW5nLCB6enp6ei4gQW5kIG1vcmUgdGV4dC4gQW5kIG1vcmUgdGV4dC4gQW5kICkgVGoNCkVUDQpCVA0KL0YxIDAwMTAgVGYNCjY5LjI1MDAgNjA0Ljk0NDAgVGQNCiggbW9yZSB0ZXh0LiBBbmQgbW9yZSB0ZXh0LiBBbmQgbW9yZSB0ZXh0LiBBbmQgbW9yZSB0ZXh0LiBBbmQgbW9yZSB0ZXh0LiApIFRqDQpFVA0KQlQNCi9GMSAwMDEwIFRmDQo2OS4yNTAwIDU5Mi45OTIwIFRkDQooIEFuZCBtb3JlIHRleHQuIEFuZCBtb3JlIHRleHQuICkgVGoNCkVUDQpCVA0KL0YxIDAwMTAgVGYNCjY5LjI1MDAgNTY5LjA4ODAgVGQNCiggQW5kIG1vcmUgdGV4dC4gQW5kIG1vcmUgdGV4dC4gQW5kIG1vcmUgdGV4dC4gQW5kIG1vcmUgdGV4dC4gQW5kIG1vcmUgKSBUag0KRVQNCkJUDQovRjEgMDAxMCBUZg0KNjkuMjUwMCA1NTcuMTM2MCBUZA0KKCB0ZXh0LiBBbmQgbW9yZSB0ZXh0LiBBbmQgbW9yZSB0ZXh0LiBFdmVuIG1vcmUuIENvbnRpbnVlZCBvbiBwYWdlIDIgLi4uKSBUag0KRVQNCmVuZHN0cmVhbQ0KZW5kb2JqDQoNCjYgMCBvYmoNCjw8DQovVHlwZSAvUGFnZQ0KL1BhcmVudCAzIDAgUg0KL1Jlc291cmNlcyA8PA0KL0ZvbnQgPDwNCi9GMSA5IDAgUiANCj4+DQovUHJvY1NldCA4IDAgUg0KPj4NCi9NZWRpYUJveCBbMCAwIDYxMi4wMDAwIDc5Mi4wMDAwXQ0KL0NvbnRlbnRzIDcgMCBSDQo+Pg0KZW5kb2JqDQoNCjcgMCBvYmoNCjw8IC9MZW5ndGggNjc2ID4+DQpzdHJlYW0NCjIgSg0KQlQNCjAgMCAwIHJnDQovRjEgMDAyNyBUZg0KNTcuMzc1MCA3MjIuMjgwMCBUZA0KKCBTaW1wbGUgUERGIEZpbGUgMiApIFRqDQpFVA0KQlQNCi9GMSAwMDEwIFRmDQo2OS4yNTAwIDY4OC42MDgwIFRkDQooIC4uLmNvbnRpbnVlZCBmcm9tIHBhZ2UgMS4gWWV0IG1vcmUgdGV4dC4gQW5kIG1vcmUgdGV4dC4gQW5kIG1vcmUgdGV4dC4gKSBUag0KRVQNCkJUDQovRjEgMDAxMCBUZg0KNjkuMjUwMCA2NzYuNjU2MCBUZA0KKCBBbmQgbW9yZSB0ZXh0LiBBbmQgbW9yZSB0ZXh0LiBBbmQgbW9yZSB0ZXh0LiBBbmQgbW9yZSB0ZXh0LiBBbmQgbW9yZSApIFRqDQpFVA0KQlQNCi9GMSAwMDEwIFRmDQo2OS4yNTAwIDY2NC43MDQwIFRkDQooIHRleHQuIE9oLCBob3cgYm9yaW5nIHR5cGluZyB0aGlzIHN0dWZmLiBCdXQgbm90IGFzIGJvcmluZyBhcyB3YXRjaGluZyApIFRqDQpFVA0KQlQNCi9GMSAwMDEwIFRmDQo2OS4yNTAwIDY1Mi43NTIwIFRkDQooIHBhaW50IGRyeS4gQW5kIG1vcmUgdGV4dC4gQW5kIG1vcmUgdGV4dC4gQW5kIG1vcmUgdGV4dC4gQW5kIG1vcmUgdGV4dC4gKSBUag0KRVQNCkJUDQovRjEgMDAxMCBUZg0KNjkuMjUwMCA2NDAuODAwMCBUZA0KKCBCb3JpbmcuICBNb3JlLCBhIGxpdHRsZSBtb3JlIHRleHQuIFRoZSBlbmQsIGFuZCBqdXN0IGFzIHdlbGwuICkgVGoNCkVUDQplbmRzdHJlYW0NCmVuZG9iag0KDQo4IDAgb2JqDQpbL1BERiAvVGV4dF0NCmVuZG9iag0KDQo5IDAgb2JqDQo8PA0KL1R5cGUgL0ZvbnQNCi9TdWJ0eXBlIC9UeXBlMQ0KL05hbWUgL0YxDQovQmFzZUZvbnQgL0hlbHZldGljYQ0KL0VuY29kaW5nIC9XaW5BbnNpRW5jb2RpbmcNCj4+DQplbmRvYmoNCg0KMTAgMCBvYmoNCjw8DQovQ3JlYXRvciAoUmF2ZSBcKGh0dHA6Ly93d3cubmV2cm9uYS5jb20vcmF2ZVwpKQ0KL1Byb2R1Y2VyIChOZXZyb25hIERlc2lnbnMpDQovQ3JlYXRpb25EYXRlIChEOjIwMDYwMzAxMDcyODI2KQ0KPj4NCmVuZG9iag0KDQp4cmVmDQowIDExDQowMDAwMDAwMDAwIDY1NTM1IGYNCjAwMDAwMDAwMTkgMDAwMDAgbg0KMDAwMDAwMDA5MyAwMDAwMCBuDQowMDAwMDAwMTQ3IDAwMDAwIG4NCjAwMDAwMDAyMjIgMDAwMDAgbg0KMDAwMDAwMDM5MCAwMDAwMCBuDQowMDAwMDAxNTIyIDAwMDAwIG4NCjAwMDAwMDE2OTAgMDAwMDAgbg0KMDAwMDAwMjQyMyAwMDAwMCBuDQowMDAwMDAyNDU2IDAwMDAwIG4NCjAwMDAwMDI1NzQgMDAwMDAgbg0KDQp0cmFpbGVyDQo8PA0KL1NpemUgMTENCi9Sb290IDEgMCBSDQovSW5mbyAxMCAwIFINCj4+DQoNCnN0YXJ0eHJlZg0KMjcxNA0KJSVFT0YNCg==',
                'title' => $ttiId.Str::random(5),
            ]);

        if (! isset($response->object()->versionNo)) {
            throw new TraderException(
                'Failed to upload TTI ID document and get version number',
                [
                    'tti_id' => $ttiId,
                    'provider_request_body' => $requestBody,
                    'provider_response_body' => $response->body(),
                ]
            );
        }

        return $response->object()->versionNo;
    }

    /**
     * @throws TraderException
     */
    public function issueMurabahaPurchaseOffer(string $ttiId, string $versionNo): void
    {
        $response = $this->soap
            ->baseWsdl($this->prefixUrl('issueMurabahaPurchaseOffer'))
            ->call('issueMurabahaPurchaseOffer', $requestBody = [
                'ttiId' => $ttiId,
                'comments' => 'create MPO',
                'ttiDocumentVersionNo' => $versionNo,
            ]);

        if (! $this->isSuccess($response)) {
            throw new TraderException(
                'Failed to issue murabaha purchase offer',
                [
                    'tti_id' => $ttiId,
                    'provider_request_body' => $requestBody,
                    'provider_response_body' => $response->body(),
                ]
            );
        }
    }

    public function sellCommodityToOpenMarket(TraderOrder $traderOrder) {}

    public function dispatchJobForTransitioningFlow(TraderOrder $traderOrder)
    {
        $lastHistory = (int) $traderOrder->last_history_action;

        $dispatchableJob = match ($traderOrder->mode) {
            TraderOrderMode::Automatic => $this->transitionFlowInAutomaticMode($lastHistory),
            TraderOrderMode::Manual => $this->transitionFlowInManualMode($lastHistory),
            default => null,
        };

        if ($dispatchableJob) {
            $dispatchableJob::dispatch($traderOrder->id);
        }
    }

    protected function transitionFlowInManualMode($lastHistoryAction): ?string
    {
        return match ($lastHistoryAction) {
            FinancingOrderHistory::CreateTransferOwnershipToLenderDocument => ProcessAskClientForWakala::class,
            FinancingOrderHistory::ContractSigned => ProcessDmccSellingCommodityToCustomerOrder::class,
            default => null,
        };
    }

    protected function transitionFlowInAutomaticMode($lastHistoryAction): ?string
    {
        return match ($lastHistoryAction) {
            FinancingOrderHistory::RespondPtp => ProcessDmccRespondedToPtpOrder::class,
            FinancingOrderHistory::CreateTransferOwnershipToLenderDocument => ProcessAskClientForWakala::class,
            FinancingOrderHistory::ContractSigned => ProcessDmccSellingCommodityToCustomerOrder::class,
            FinancingOrderHistory::CreateSellingCommodityToCustomerDocument => ProcessDmccMpoOrder::class,
            default => null,
        };
    }

    public function isTraderOrderCancellable(TraderOrder $traderOrder, ?string $area)
    {
        if ($traderOrder->status->isNot(TraderOrderStatus::InProgress)) {
            return false;
        }

        $traderHistoryActions = $traderOrder->traderHistories->pluck('action')->toArray();

        return empty(array_intersect(self::notCancellableActions, $traderHistoryActions));
    }

    /**
     * @return string <Driver>_<trader_orders.reference_number>.pdf
     */
    public function generatePdfFileName($traderOrder, $collectionName): string
    {
        return $traderOrder->provider.'-'.$traderOrder->reference.'.pdf';
    }

    // use it in public api to proceed order after purchasing commodity step by one step
    public function processProceedContractAndClientWakala(TraderOrder $traderOrder)
    {
        ProcessProceedContractAndClientWakala::dispatchSync($traderOrder->id);
    }

    public function checkCanInitiateTraderOrder()
    {
        return true;
    }

    public function moveHoldTraderOrder(TraderOrder $trader)
    {
        return true;
    }

    public function HoverMessageOfTraderStatus(TraderOrder $traderOrder): ?string
    {
        return null;
    }

    public function contractSignedMessage(TraderOrder $traderOrder)
    {
        return null;
    }

    public function retryOrder(TraderOrder $traderOrder)
    {
        $traderOrder->order->update(['status' => FinancingOrderStatus::Approved]);
    }

    public function confirmCancelledFromProvider(TraderOrder $traderOrder): void {}
}
