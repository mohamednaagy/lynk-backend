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

    public function transform($traderHistoryKey): array
    {
        $traderOrderHistoryExist = $this->traderHistories->where('action', $traderHistoryKey)->first();

        $getPtpDocument = $this->traderHistories->where('action', FinancingOrderHistory::GetPtpDocument)->first();

        $transferOwnershipToLender = $this->traderHistories
            ->where(
                'action',
                FinancingOrderHistory::CreateTransferOwnershipToLenderDocument
            )
            ->first();

        $getMurabahaPurchaseOfferDocument = $this->traderHistories
            ->where('action', FinancingOrderHistory::GetMurabahaPurchaseOfferDocument)
            ->first();

        $getWarrantAmendmentExceptWarrantNoDocument = $this->traderHistories
            ->where('action', FinancingOrderHistory::GetWarrantAmendmentExceptWarrantNoDocument)
            ->first();

        $signedWakalaMedia = $this->traderOrder->getFirstMedia(TraderOrderMediaCollection::SignedClientWakala);

        $wakalaMedia = $this->traderOrder->getFirstMedia(TraderOrderMediaCollection::ClientWakala);

        return match ($traderHistoryKey) {
            FinancingOrderHistory::ClientWakalaAccepted => [
                'step' => 'client_wakala',
                'is_complete' => (bool) $traderOrderHistoryExist,
                'completed_at' => optional($traderOrderHistoryExist)->created_at?->format('Y-m-d h:i:s A'),
                'duration' => $this->getDurationForHistoryStep($traderHistoryKey),
                'wakala_document' => [
                    'url' => $wakalaMedia?->file_url,
                    'date' => optional($wakalaMedia)->created_at?->format('Y-m-d h:i:s A'),
                ],
                'signed_wakala_document' => [
                    'url' => $signedWakalaMedia?->file_url,
                    'date' => optional($signedWakalaMedia)->created_at?->format('Y-m-d h:i:s A'),
                ],
            ],
            FinancingOrderHistory::CreateTransferOwnershipToLenderDocument => [
                'step' => 'commodity_purchased',
                'is_complete' => (bool) $traderOrderHistoryExist,
                'completed_at' => optional($traderOrderHistoryExist)->created_at?->format('Y-m-d h:i:s A'),
                'cert_document' => [
                    'url' => $this->traderOrder
                        ->getFirstMedia(TraderOrderMediaCollection::TtiHoldingCertificate)
                        ?->file_url,
                    'date' => optional($getPtpDocument)->created_at?->format('Y-m-d h:i:s A'),
                ],
                'ownership_document' => [
                    'url' => $this->traderOrder
                        ->getFirstMedia(TraderOrderMediaCollection::TransferOwnershipToLender)
                        ?->file_url,
                    'date' => optional($transferOwnershipToLender)->created_at?->format('Y-m-d h:i:s A'),
                ],
                'duration' => $this->getDurationForHistoryStep($traderHistoryKey),
            ],
            FinancingOrderHistory::ContractSigned => [
                'step' => 'contract_singed',
                'is_complete' => (bool) $traderOrderHistoryExist,
                'completed_at' => optional($traderOrderHistoryExist)->created_at?->format('Y-m-d h:i:s A'),
                'duration' => $this->getDurationForHistoryStep(FinancingOrderHistory::ContractSigned),

            ],
            FinancingOrderHistory::CreateSellingCommodityToCustomerDocument => [
                'step' => 'selling_commodity_to_customer',
                'is_complete' => (bool) $traderOrderHistoryExist,
                'completed_at' => optional($traderOrderHistoryExist)->created_at?->format('Y-m-d h:i:s A'),
                'duration' => $this->getDurationForHistoryStep($traderHistoryKey),
                'document' => $this->traderOrder
                    ->getFirstMedia(TraderOrderMediaCollection::SellingCommodityToCustomer)
                    ?->file_url,
            ],
            FinancingOrderHistory::IssueMurabahaOffer => [
                'step' => 'selling_commodity_to_open_market',
                'is_complete' => (bool) $this->traderHistories->where('action', FinancingOrderHistory::AttachMpoDocument)->first(),
                'completed_at' => optional($traderOrderHistoryExist)->created_at?->format('Y-m-d h:i:s A'),
                'mpo_document' => [
                    'url' => $this->traderOrder
                        ->getFirstMedia(TraderOrderMediaCollection::MurabahaPurchaseOrder)
                        ?->file_url,
                    'date' => optional($getMurabahaPurchaseOfferDocument)->created_at?->format('Y-m-d h:i:s A'),
                ],
                'duration' => $this->getDurationForHistoryStep($traderHistoryKey),

            ],
            FinancingOrderHistory::MurabahaSaleCompleted => [
                'step' => 'murabha_sale_completed',
                'is_complete' => (bool) $traderOrderHistoryExist,
                'completed_at' => optional($traderOrderHistoryExist)->created_at?->format('Y-m-d h:i:s A'),
                'warranty_document' => [
                    'url' => $this->traderOrder
                        ->getFirstMedia(TraderOrderMediaCollection::WarrantAmendmentExceptWarrantNo)
                        ?->file_url,
                    'date' => optional($getWarrantAmendmentExceptWarrantNoDocument)->created_at?->format('Y-m-d h:i:s A'),
                ],
                'duration' => $this->getDurationForHistoryStep($traderHistoryKey),
            ],
            default => null,
        };
    }

    public function getDurationForHistoryStep($step)
    {
        $financingOrderStatus = app(StepHistoriesDictionary::class)
            ->getStepByHistory($step)
            ?->status;

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

            $diffTime = Str::remove($ignoredWords, $diffTime);

            return $latestAction->created_at->format('Y-m-d h:m A')
                .__('common.processing_time', ['time' => $diffTime]);
        }
    }

    private function getLatestTraderHistoryForPreviousStatusOfStatus($status)
    {
        $previousStepActions = app(StepHistoriesDictionary::class)
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
        $stepActions = app(StepHistoriesDictionary::class)
            ->getStepOf($status)
            ?->histories
            ?? [];

        return $this->traderHistories->whereIn('action', $stepActions)
            ->sortBy('updated_at', descending: true)
            ->first();
    }
}
