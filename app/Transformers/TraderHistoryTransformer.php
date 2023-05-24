<?php

namespace App\Transformers;

use App\Enums\FinancingOrderHistory;
use App\Enums\MediaCollections\TraderOrderMediaCollection;
use App\Models\TraderOrder;
use App\Support\FinancingOrders\StepAndHistories\StepHistoriesDictionary;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
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

    public function transform($hestory)
    {
        $fitlter = $this->traderOrder->provider.'.'.$this->traderOrder->version;

        return match ($fitlter) {
            'bursam.v1', 'fake.v1', 'dmcc.v1' => $this->getDmccV1MurabahaStep($hestory),
            'bursam.v2' => $this->getBursamV2MurabahaStep($hestory),
        };
    }

    public function getBursamV2MurabahaStep($traderHistoryKey): array
    {
        $history = $this->getHistory($traderHistoryKey);

        return match ($traderHistoryKey) {
            FinancingOrderHistory::ClientWakalaAccepted => [
                'step' => 'client_wakala',
                'is_complete' => (bool) $history,
                'completed_at' => optional($history)->created_at?->format('Y-m-d h:i:s A'),
                'duration' => $this->getDurationForHistoryStep($traderHistoryKey),
                'signed_wakala_document' => [
                    'url' => $this->getMedia(TraderOrderMediaCollection::SignedClientWakala)?->file_url,
                    'date' => optional($this->getMedia(TraderOrderMediaCollection::SignedClientWakala))->created_at?->format('Y-m-d h:i:s A'),
                ],
            ],
            FinancingOrderHistory::CreateTransferOwnershipToLenderDocument => [
                'step' => 'commodity_purchased',
                'is_complete' => (bool) $history,
                'completed_at' => optional($history)->created_at?->format('Y-m-d h:i:s A'),
                'cert_document' => [
                    'url' => $this->traderOrder
                        ->getFirstMedia(TraderOrderMediaCollection::TtiHoldingCertificate)
                        ?->file_url,
                    'date' => optional($this->getHistory(FinancingOrderHistory::GetPtpDocument))->created_at?->format('Y-m-d h:i:s A'),
                ],
                'ownership_document' => [
                    'url' => $this->traderOrder
                        ->getFirstMedia(TraderOrderMediaCollection::TransferOwnershipToLender)
                        ?->file_url,
                    'date' => optional($this->getHistory(FinancingOrderHistory::CreateTransferOwnershipToLenderDocument))->created_at?->format('Y-m-d h:i:s A'),
                ],
                'duration' => $this->getDurationForHistoryStep($traderHistoryKey),
            ],
            FinancingOrderHistory::ContractSigned => [
                'step' => 'contract_signed',
                'wakala_document' => [
                    'url' => $this->getMedia(TraderOrderMediaCollection::ClientWakala)?->file_url,
                    'date' => optional($this->getMedia(TraderOrderMediaCollection::ClientWakala))->created_at?->format('Y-m-d h:i:s A'),
                ],
                'is_complete' => (bool) $history,
                'completed_at' => optional($history)->created_at?->format('Y-m-d h:i:s A'),
                'duration' => $this->getDurationForHistoryStep(FinancingOrderHistory::ContractSigned),

            ],
            FinancingOrderHistory::CreateSellingCommodityToCustomerDocument => [
                'step' => 'selling_commodity_to_customer',
                'is_complete' => (bool) $history,
                'completed_at' => optional($history)->created_at?->format('Y-m-d h:i:s A'),
                'duration' => $this->getDurationForHistoryStep($traderHistoryKey),
                'document' => $this->traderOrder
                    ->getFirstMedia(TraderOrderMediaCollection::SellingCommodityToCustomer)
                    ?->file_url,
            ],
            FinancingOrderHistory::MurabahaSaleCompleted => [
                'step' => 'murabha_sale_completed',
                'is_complete' => (bool) $history,
                'completed_at' => optional($history)->created_at?->format('Y-m-d h:i:s A'),
                'warranty_document' => [
                    'url' => $this->traderOrder
                        ->getFirstMedia(TraderOrderMediaCollection::WarrantAmendmentExceptWarrantNo)
                        ?->file_url,
                    'date' => optional($this->getHistory(FinancingOrderHistory::GetWarrantAmendmentExceptWarrantNoDocument))->created_at?->format('Y-m-d h:i:s A'),
                ],
                'duration' => $this->getDurationForHistoryStep($traderHistoryKey),
            ],
            default => [],
        };
    }

    public function getDmccV1MurabahaStep($traderHistoryKey): array
    {
        $history = $this->getHistory($traderHistoryKey);

        return match ($traderHistoryKey) {
            FinancingOrderHistory::ClientWakalaAccepted => [
                'step' => 'client_wakala',
                'is_complete' => (bool) $history,
                'completed_at' => optional($history)->created_at?->format('Y-m-d h:i:s A'),
                'duration' => $this->getDurationForHistoryStep($traderHistoryKey),
                'signed_wakala_document' => [
                    'url' => $this->getMedia(TraderOrderMediaCollection::SignedClientWakala)?->file_url,
                    'date' => optional($this->getMedia(TraderOrderMediaCollection::SignedClientWakala))->created_at?->format('Y-m-d h:i:s A'),
                ],
            ],
            FinancingOrderHistory::CreateTransferOwnershipToLenderDocument => [
                'step' => 'commodity_purchased',
                'is_complete' => (bool) $history,
                'completed_at' => optional($history)->created_at?->format('Y-m-d h:i:s A'),
                'cert_document' => [
                    'url' => $this->traderOrder
                        ->getFirstMedia(TraderOrderMediaCollection::TtiHoldingCertificate)
                        ?->file_url,
                    'date' => optional($this->getHistory(FinancingOrderHistory::GetPtpDocument))->created_at?->format('Y-m-d h:i:s A'),
                ],
                'ownership_document' => [
                    'url' => $this->traderOrder
                        ->getFirstMedia(TraderOrderMediaCollection::TransferOwnershipToLender)
                        ?->file_url,
                    'date' => optional($this->getHistory(FinancingOrderHistory::CreateTransferOwnershipToLenderDocument))->created_at?->format('Y-m-d h:i:s A'),
                ],
                'duration' => $this->getDurationForHistoryStep($traderHistoryKey),
            ],
            FinancingOrderHistory::ContractSigned => [
                'step' => 'contract_signed',
                'wakala_document' => [
                    'url' => $this->getMedia(TraderOrderMediaCollection::ClientWakala)?->file_url,
                    'date' => optional($this->getMedia(TraderOrderMediaCollection::ClientWakala))->created_at?->format('Y-m-d h:i:s A'),
                ],
                'is_complete' => (bool) $history,
                'completed_at' => optional($history)->created_at?->format('Y-m-d h:i:s A'),
                'duration' => $this->getDurationForHistoryStep(FinancingOrderHistory::ContractSigned),

            ],
            FinancingOrderHistory::CreateSellingCommodityToCustomerDocument => [
                'step' => 'selling_commodity_to_customer',
                'is_complete' => (bool) $history,
                'completed_at' => optional($history)->created_at?->format('Y-m-d h:i:s A'),
                'duration' => $this->getDurationForHistoryStep($traderHistoryKey),
                'document' => $this->traderOrder
                    ->getFirstMedia(TraderOrderMediaCollection::SellingCommodityToCustomer)
                    ?->file_url,
            ],
            FinancingOrderHistory::IssueMurabahaOffer => [
                'step' => 'selling_commodity_to_open_market',
                'is_complete' => (bool) $this->traderHistories->where('action', FinancingOrderHistory::AttachMpoDocument)->first(),
                'completed_at' => optional($history)->created_at?->format('Y-m-d h:i:s A'),
                'mpo_document' => [
                    'url' => $this->traderOrder
                        ->getFirstMedia(TraderOrderMediaCollection::MurabahaPurchaseOrder)
                        ?->file_url,
                    'date' => optional($this->getHistory(FinancingOrderHistory::GetMurabahaPurchaseOfferDocument))->created_at?->format('Y-m-d h:i:s A'),
                ],
                'duration' => $this->getDurationForHistoryStep($traderHistoryKey),

            ],
            FinancingOrderHistory::MurabahaSaleCompleted => [
                'step' => 'murabha_sale_completed',
                'is_complete' => (bool) $history,
                'completed_at' => optional($history)->created_at?->format('Y-m-d h:i:s A'),
                'warranty_document' => [
                    'url' => $this->traderOrder
                        ->getFirstMedia(TraderOrderMediaCollection::WarrantAmendmentExceptWarrantNo)
                        ?->file_url,
                    'date' => optional($this->getHistory(FinancingOrderHistory::GetWarrantAmendmentExceptWarrantNoDocument))->created_at?->format('Y-m-d h:i:s A'),
                ],
                'duration' => $this->getDurationForHistoryStep($traderHistoryKey),
            ],
            default => [],
        };
    }

    public function getDurationForHistoryStep($history)
    {
        $financingOrderStatus = (new StepHistoriesDictionary($this->traderOrder->provider, $this->traderOrder->version))
            ->getStepByHistory($history)
            ?->step;

        if (blank($financingOrderStatus)) {
            return null;
        }

        $previousAction = $this->getLatestTraderHistoryForPreviousStatusOfStatus($financingOrderStatus);
        $latestAction = $this->getLatestTraderHistoryForStatus($financingOrderStatus);

        if ($previousAction?->created_at && $latestAction?->created_at) {
            $diffTime = $previousAction->created_at->diffForHumans(
                $latestAction->created_at,
                [
                    'parts' => 3,
                    'join' => true,
                ]
            );

            $ignoredWords = ['ago', 'before', 'after', 'منذ', 'قبل'];

            return Str::remove($ignoredWords, $diffTime);
        }

        return null;
    }

    private function getLatestTraderHistoryForPreviousStatusOfStatus($status)
    {
        $previousStepActions = (new StepHistoriesDictionary($this->traderOrder->provider, $this->traderOrder->version))
            ->getPreviousStepOf($status)
            ?->histories;

        return blank($previousStepActions)
            ? null
            : $this->traderHistories->whereIn('action', $previousStepActions)
                ->sortBy('updated_at', descending: true)
                ->first();
    }

    private function getLatestTraderHistoryForStatus($status)
    {
        $stepActions = (new StepHistoriesDictionary($this->traderOrder->provider, $this->traderOrder->version))
            ->getStepOf($status)
            ?->histories
            ?? [];

        return $this->traderHistories->whereIn('action', $stepActions)
            ->sortBy('updated_at', descending: true)
            ->first();
    }

    public function getHistory($history)
    {
        return $this->traderHistories->where('action', $history)->first();
    }

    public function getMedia($media)
    {
        return $this->traderOrder->getFirstMedia($media);
    }
}
