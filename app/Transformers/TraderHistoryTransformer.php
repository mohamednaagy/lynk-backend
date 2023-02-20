<?php

namespace App\Transformers;

use App\Enums\FinancingOrderHistory;
use App\Enums\MediaCollections\TraderOrderMediaCollection;
use App\Models\TraderOrder;
use Illuminate\Support\Collection;
use League\Fractal\TransformerAbstract;

class TraderHistoryTransformer extends TransformerAbstract
{
    protected TraderOrder $traderOrder;

    protected Collection $traderHistories;

    public function __construct(?TraderOrder $traderOrder)
    {
        $this->traderOrder = $traderOrder;
        $this->traderHistories = $traderOrder->traderHistories ?? collect();
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

        return match ($traderHistoryKey) {
            FinancingOrderHistory::ClientWakalaAccepted => [
                'step' => 'client_wakala',
                'is_complete' => (bool) $traderOrderHistoryExist,
                'completed_at' => optional($traderOrderHistoryExist)->created_at?->format('Y-m-d h:i:s A'),
                'document' => $this->fileUrl($this->traderOrder->getFirstMedia(TraderOrderMediaCollection::ClientWakala)),
            ],
            FinancingOrderHistory::CreateTransferOwnershipToLenderDocument => [
                'step' => 'commodity_purchased',
                'is_complete' => (bool) $traderOrderHistoryExist,
                'completed_at' => optional($traderOrderHistoryExist)->created_at?->format('Y-m-d h:i:s A'),
                'cert_document' => [
                    'url' => $this->fileUrl($this->traderOrder->getFirstMedia(TraderOrderMediaCollection::TtiHoldingCertificate)),
                    'date' => optional($getPtpDocument)->created_at?->format('Y-m-d h:i:s A'),
                ],
                'ownership_document' => [
                    'url' => $this->fileUrl($this->traderOrder->getFirstMedia(TraderOrderMediaCollection::TransferOwnershipToLender)),
                    'date' => optional($transferOwnershipToLender)->created_at?->format('Y-m-d h:i:s A'),
                ],
            ],
            FinancingOrderHistory::ContractSigned => [
                'step' => 'contract_singed',
                'is_complete' => (bool) $traderOrderHistoryExist,
                'completed_at' => optional($traderOrderHistoryExist)->created_at?->format('Y-m-d h:i:s A'),
            ],
            FinancingOrderHistory::CreateSellingCommodityToCustomerDocument => [
                'step' => 'selling_commodity_to_customer',
                'is_complete' => (bool) $traderOrderHistoryExist,
                'completed_at' => optional($traderOrderHistoryExist)->created_at?->format('Y-m-d h:i:s A'),
                'document' => $this->fileUrl($this->traderOrder->getFirstMedia(TraderOrderMediaCollection::SellingCommodityToCustomer)),
            ],
            FinancingOrderHistory::IssueMurabahaOffer => [
                'step' => 'selling_commodity_to_open_market',
                'is_complete' => (bool) $this->traderHistories->where('action', FinancingOrderHistory::AttachMpoDocument)->first(),
                'completed_at' => optional($traderOrderHistoryExist)->created_at?->format('Y-m-d h:i:s A'),
                'mpo_document' => [
                    'url' => $this->fileUrl($this->traderOrder->getFirstMedia(TraderOrderMediaCollection::MurabahaPurchaseOrder)),
                    'date' => optional($getMurabahaPurchaseOfferDocument)->created_at?->format('Y-m-d h:i:s A'),
                ],
            ],
            FinancingOrderHistory::MurabahaSaleCompleted => [
                'step' => 'murabha_sale_completed',
                'is_complete' => (bool) $traderOrderHistoryExist,
                'completed_at' => optional($traderOrderHistoryExist)->created_at?->format('Y-m-d h:i:s A'),
                'warranty_document' => [
                    'url' => $this->fileUrl($this->traderOrder->getFirstMedia(TraderOrderMediaCollection::WarrantAmendmentExceptWarrantNo)),
                    'date' => optional($getWarrantAmendmentExceptWarrantNoDocument)->created_at?->format('Y-m-d h:i:s A'),
                ],
            ],
            default => null,
        };
    }

    public function fileUrl($media): ?string
    {
        if ($media) {
            return route('api.v1.media.download', ['media' => $media->uuid]);
        }

        return null;
    }
}
