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

    public function transform(int $traderHistoryKey): array
    {
        $traderOrderHistoryExist = $this->traderHistories->where('action', $traderHistoryKey)->first();
        $getPtpDocument = $this->traderHistories->where('action', FinancingOrderHistory::GetPtpDocument)->first();
        $transferOwnershipToLender = $this->traderHistories->where('action', FinancingOrderHistory::CreateTransferOwnershipToLenderDocument)->first();
        $getMurabahaPurchaseOfferDocument = $this->traderHistories->where('action', FinancingOrderHistory::GetMurabahaPurchaseOfferDocument)->first();
        $getWarrantAmendmentExceptWarrantNoDocument = $this->traderHistories->where('action', FinancingOrderHistory::GetWarrantAmendmentExceptWarrantNoDocument)->first();

        $data = match ($traderHistoryKey) {
            0 => [
                'step' => 'client_wakala',
                'is_complete' => (bool) $this->financingOrder->client_wakala_accepted_at,
                'completed_at' => $this->financingOrder->client_wakala_accepted_at ?? null,
                'document' => $this->financingOrder->getMedia('client_wakala')->first()
                    ? $this->financingOrder->getMedia('client_wakala')->first()->getUrl()
                    : null,
            ],
            5 => [
                'step' => 'commodity_purchased',
                'is_complete' => (bool) $traderOrderHistoryExist,
                'completed_at' => $traderOrderHistoryExist
                    ? $traderOrderHistoryExist->created_at
                    : null,
                'cert_document' => [
                    'url' => $this->financingOrder->getMedia('client_wakala')->first()
                        ? $this->financingOrder->getMedia('client_wakala')->first()->getUrl()
                        : null,
                    'date' => $getPtpDocument
                        ? $getPtpDocument->created_at
                        : null,
                ],
                'ownership_document' => [
                    'url' => $this->financingOrder->getMedia('transfer_ownership_to_lender')->first()
                        ? $this->financingOrder->getMedia('transfer_ownership_to_lender')->first()->getUrl()
                        : null,
                    'date' => $transferOwnershipToLender
                        ? $transferOwnershipToLender->created_at
                        : null,
                ],
            ],
            13 => [
                'step' => 'contract_singed',
                'is_complete' => (bool) $traderOrderHistoryExist,
                'completed_at' => $traderOrderHistoryExist
                    ? $traderOrderHistoryExist->created_at
                    : null,
            ],
            2 => [
                'step' => 'selling_commodity_to_customer',
                'is_complete' => (bool) $traderOrderHistoryExist,
                'completed_at' => $traderOrderHistoryExist
                    ? $traderOrderHistoryExist->created_at
                    : null,
                'document' => $this->financingOrder->getMedia('selling_commodity_to_customer')->first()
                    ? $this->financingOrder->getMedia('selling_commodity_to_customer')->first()->getUrl()
                    : null,
            ],
            10 => [
                'step' => 'selling_commodity_to_open_market',
                'is_complete' => (bool) $traderOrderHistoryExist,
                'completed_at' => $traderOrderHistoryExist
                    ? $traderOrderHistoryExist->created_at
                    : null,
                'mpo_document' => [
                    'url' => $this->financingOrder->getMedia('murabaha_purchase_order')->first()
                        ? $this->financingOrder->getMedia('murabaha_purchase_order')->first()->getUrl()
                        : null,
                    'date' => $getMurabahaPurchaseOfferDocument
                        ? $getMurabahaPurchaseOfferDocument->created_at
                        : null,
                ],
                'warranty_document' => [
                    'url' => $this->financingOrder->getMedia('warrant_amendment_except_warrant_no')->first()
                        ? $this->financingOrder->getMedia('warrant_amendment_except_warrant_no')->first()->getUrl()
                        : null,
                    'date' => $getWarrantAmendmentExceptWarrantNoDocument
                        ? $getWarrantAmendmentExceptWarrantNoDocument->created_at
                        : null,
                ],
            ],
            11 => [
                'step' => 'murabha_sale_completed',
                'is_complete' => (bool) $traderOrderHistoryExist,
                'completed_at' => $traderOrderHistoryExist
                    ? $traderOrderHistoryExist->created_at
                    : null,
            ],
            default => null,
        };

        return $data;
    }
}
