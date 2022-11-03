<?php

namespace App\Transformers;

use App\Enums\FinancingOrderHistory;
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
                'document' => optional($this->financingOrder->getMedia('client_wakala')->first())->getUrl(),
            ],
            FinancingOrderHistory::CommodityPurchased => [
                'step' => 'commodity_purchased',
                'is_complete' => (bool) $traderOrderHistoryExist,
                'completed_at' => optional(optional($traderOrderHistoryExist)->created_at)->format('Y-m-d h:m A'),
                'cert_document' => [
                    'url' => optional($this->financingOrder->getMedia('client_wakala')->first())->getUrl(),
                    'date' => optional(optional($getPtpDocument)->created_at)->format('Y-m-d h:m A'),
                ],
                'ownership_document' => [
                    'url' => optional($this->financingOrder->getMedia('transfer_ownership_to_lender')->first())->getUrl(),
                    'date' => optional(optional($transferOwnershipToLender)->created_at)->format('Y-m-d h:m A'),
                ],
            ],
            FinancingOrderHistory::ContractSigned => [
                'step' => 'contract_singed',
                'is_complete' => (bool) $traderOrderHistoryExist,
                'completed_at' => optional(optional($traderOrderHistoryExist)->created_at)->format('Y-m-d h:m A'),
            ],
            FinancingOrderHistory::CreateSellingCommodityToCustomerDocument => [
                'step' => 'selling_commodity_to_customer',
                'is_complete' => (bool) $traderOrderHistoryExist,
                'completed_at' => optional(optional($traderOrderHistoryExist)->created_at)->format('Y-m-d h:m A'),
                'document' => optional($this->financingOrder->getMedia('selling_commodity_to_customer')->first())->getUrl(),
            ],
            FinancingOrderHistory::IssueMurabahaOffer => [
                'step' => 'selling_commodity_to_open_market',
                'is_complete' => (bool) $traderOrderHistoryExist,
                'completed_at' => optional(optional($traderOrderHistoryExist)->created_at)->format('Y-m-d h:m A'),
                'mpo_document' => [
                    'url' => optional($this->financingOrder->getMedia('murabaha_purchase_order')->first())->getUrl(),
                    'date' => optional(optional($getMurabahaPurchaseOfferDocument)->created_at)->format('Y-m-d h:m A'),
                ],
                'warranty_document' => [
                    'url' => optional($this->financingOrder->getMedia('warrant_amendment_except_warrant_no')->first())->getUrl(),
                    'date' => optional(optional($getWarrantAmendmentExceptWarrantNoDocument)->created_at)->format('Y-m-d h:m A'),
                ],
            ],
            FinancingOrderHistory::MurabahaSaleCompleted => [
                'step' => 'murabha_sale_completed',
                'is_complete' => (bool) $traderOrderHistoryExist,
                'completed_at' => optional(optional($traderOrderHistoryExist)->created_at)->format('Y-m-d h:m A'),
            ],
            default => null,
        };

        return $data;
    }
}
