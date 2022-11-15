<?php

namespace App\Transformers;

use App\Enums\FinancingOrderHistory;
use App\Enums\MediaCollections\FinancingOrderMediaCollection;
use App\Models\FinancingOrder;
use Illuminate\Support\Collection;
use League\Fractal\TransformerAbstract;

class TraderHistoryTransformer extends TransformerAbstract
{
    protected FinancingOrder $financingOrder;

    protected Collection $traderHistories;

    public function __construct(FinancingOrder $financingOrder, Collection $traderHistories)
    {
        $this->financingOrder = $financingOrder;
        $this->traderHistories = $traderHistories;
    }

    protected array $defaultIncludes = [];

    protected array $availableIncludes = [];

    public function transform($traderHistoryKey): array
    {
        $traderOrderHistoryExist = $this->traderHistories->where('action', $traderHistoryKey)->first();
        $getPtpDocument = $this->traderHistories->where('action', FinancingOrderHistory::GetPtpDocument)->first();
        $transferOwnershipToLender = $this->traderHistories->where('action', FinancingOrderHistory::CreateTransferOwnershipToLenderDocument)->first();
        $getMurabahaPurchaseOfferDocument = $this->traderHistories->where('action', FinancingOrderHistory::GetMurabahaPurchaseOfferDocument)->first();
        $getWarrantAmendmentExceptWarrantNoDocument = $this->traderHistories->where('action', FinancingOrderHistory::GetWarrantAmendmentExceptWarrantNoDocument)->first();

        $data = match ($traderHistoryKey) {
            'client_wakala' => [
                'step' => 'client_wakala',
                'is_complete' => (bool) $this->financingOrder->client_wakala_accepted_at,
                'completed_at' => optional($this->financingOrder->client_wakala_accepted_at)->format('Y-m-d h:m A'),
                'document' => optional($this->financingOrder->getMedia(FinancingOrderMediaCollection::ClientWakala)->first())->getUrl(),
            ],
            FinancingOrderHistory::CreateTransferOwnershipToLenderDocument => [
                'step' => 'commodity_purchased',
                'is_complete' => (bool) $traderOrderHistoryExist,
                'completed_at' => optional($traderOrderHistoryExist)->created_at?->format('Y-m-d h:m A'),
                'cert_document' => [
                    'url' => optional($this->financingOrder->getMedia(FinancingOrderMediaCollection::ClientWakala)->first())->getUrl(),
                    'date' => optional($getPtpDocument)->created_at?->format('Y-m-d h:m A'),
                ],
                'ownership_document' => [
                    'url' => optional($this->financingOrder->getMedia(FinancingOrderMediaCollection::TransferOwnershipToLender)->first())->getUrl(),
                    'date' => optional($transferOwnershipToLender)->created_at?->format('Y-m-d h:m A'),
                ],
            ],
            FinancingOrderHistory::ContractSigned => [
                'step' => 'contract_singed',
                'is_complete' => (bool) $traderOrderHistoryExist,
                'completed_at' => optional($traderOrderHistoryExist)->created_at?->format('Y-m-d h:m A'),
            ],
            FinancingOrderHistory::CreateSellingCommodityToCustomerDocument => [
                'step' => 'selling_commodity_to_customer',
                'is_complete' => (bool) $traderOrderHistoryExist,
                'completed_at' => optional($traderOrderHistoryExist)->created_at?->format('Y-m-d h:m A'),
                'document' => optional($this->financingOrder->getMedia(FinancingOrderMediaCollection::SellingCommodityToCustomer)->first())->getUrl(),
            ],
            FinancingOrderHistory::IssueMurabahaOffer => [
                'step' => 'selling_commodity_to_open_market',
                'is_complete' => (bool) $traderOrderHistoryExist,
                'completed_at' => optional($traderOrderHistoryExist)->created_at?->format('Y-m-d h:m A'),
                'mpo_document' => [
                    'url' => optional($this->financingOrder->getMedia(FinancingOrderMediaCollection::MurabahaPurchaseOrder)->first())->getUrl(),
                    'date' => optional($getMurabahaPurchaseOfferDocument)->created_at?->format('Y-m-d h:m A'),
                ],
                'warranty_document' => [
                    'url' => optional($this->financingOrder->getMedia(FinancingOrderMediaCollection::WarrantAmendmentExceptWarrantNo)->first())->getUrl(),
                    'date' => optional($getWarrantAmendmentExceptWarrantNoDocument)->created_at?->format('Y-m-d h:m A'),
                ],
            ],
            FinancingOrderHistory::MurabahaSaleCompleted => [
                'step' => 'murabha_sale_completed',
                'is_complete' => (bool) $traderOrderHistoryExist,
                'completed_at' => optional($traderOrderHistoryExist)->created_at?->format('Y-m-d h:m A'),
            ],
            default => null,
        };

        return $data;
    }
}
