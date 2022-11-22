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
                'completed_at' => optional($this->financingOrder->client_wakala_accepted_at)->format('Y-m-d h:i:s A'),
                'document' => $this->fileUrl($this->financingOrder->getMedia(FinancingOrderMediaCollection::ClientWakala)->first()),
            ],
            FinancingOrderHistory::CreateTransferOwnershipToLenderDocument => [
                'step' => 'commodity_purchased',
                'is_complete' => (bool) $traderOrderHistoryExist,
                'completed_at' => optional($traderOrderHistoryExist)->created_at?->format('Y-m-d h:i:s A'),
                'cert_document' => [
                    'url' => $this->fileUrl($this->financingOrder->getMedia(FinancingOrderMediaCollection::TtiHoldingCertificate)->first()),
                    'date' => optional($getPtpDocument)->created_at?->format('Y-m-d h:i:s A'),
                ],
                'ownership_document' => [
                    'url' => $this->fileUrl($this->financingOrder->getMedia(FinancingOrderMediaCollection::TransferOwnershipToLender)->first()),
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
                'document' => $this->fileUrl($this->financingOrder->getMedia(FinancingOrderMediaCollection::SellingCommodityToCustomer)->first()),
            ],
            FinancingOrderHistory::IssueMurabahaOffer => [
                'step' => 'selling_commodity_to_open_market',
                'is_complete' => (bool) $this->traderHistories->where('action', FinancingOrderHistory::GetWarrantAmendmentExceptWarrantNoDocument)->first(),
                'completed_at' => optional($traderOrderHistoryExist)->created_at?->format('Y-m-d h:i:s A'),
                'mpo_document' => [
                    'url' => $this->fileUrl($this->financingOrder->getMedia(FinancingOrderMediaCollection::MurabahaPurchaseOrder)->first()),
                    'date' => optional($getMurabahaPurchaseOfferDocument)->created_at?->format('Y-m-d h:i:s A'),
                ],
                'warranty_document' => [
                    'url' => $this->fileUrl($this->financingOrder->getMedia(FinancingOrderMediaCollection::WarrantAmendmentExceptWarrantNo)->first()),
                    'date' => optional($getWarrantAmendmentExceptWarrantNoDocument)->created_at?->format('Y-m-d h:i:s A'),
                ],
            ],
            FinancingOrderHistory::MurabahaSaleCompleted => [
                'step' => 'murabha_sale_completed',
                'is_complete' => (bool) $traderOrderHistoryExist,
                'completed_at' => optional($traderOrderHistoryExist)->created_at?->format('Y-m-d h:i:s A'),
            ],
            default => null,
        };

        return $data;
    }

    public function fileUrl($media)
    {
        if ($media) {
            return route('api.v1.media.download', ['media' => $media->uuid]);
        }

        return null;
    }
}
