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

        $historyPurchaseComidityDate = $this->traderOrder->provider != 'bursam' ? FinancingOrderHistory::GetPtpDocument : FinancingOrderHistory::AttachTtiHoldingCertificateDocument;

        $getPurchaseComidityDate = $this->traderHistories->where('action', $historyPurchaseComidityDate)->first();

        $transferOwnershipToLender = $this->traderHistories
            ->where(
                'action',
                FinancingOrderHistory::CreateTransferOwnershipToLenderDocument
            )
            ->first();

        $getMurabahaPurchaseOfferDocument = $this->traderHistories
            ->where('action', FinancingOrderHistory::GetMurabahaPurchaseOfferDocument)
            ->first();

        $signedWakalaMedia = $this->traderOrder->getFirstMedia(TraderOrderMediaCollection::SignedClientWakala);

        $wakalaMedia = $this->traderOrder->getFirstMedia(TraderOrderMediaCollection::ClientWakala);

        $historyMurabhaSaleCompleted = $this->traderOrder->provider != 'bursam' ? FinancingOrderHistory::GetWarrantAmendmentExceptWarrantNoDocument : FinancingOrderHistory::GetOwnershipToCustomerCertificate;

        $mediaMurabhaSaleCompleted = $this->traderOrder->provider != 'bursam' ? TraderOrderMediaCollection::WarrantAmendmentExceptWarrantNo : TraderOrderMediaCollection::BursamSellingCommodityToCustomer;

        $historyStbCertificate = $this->traderHistories->where('action', FinancingOrderHistory::GetOwnershipToCustomerCertificate)->first();

        return match ($traderHistoryKey) {
            FinancingOrderHistory::ClientWakalaAccepted => [
                'step' => 'client_wakala',
                'is_complete' => (bool) $traderOrderHistoryExist,
                'completed_at' => optional($traderOrderHistoryExist)->created_at?->format('Y-m-d h:i:s A'),
                'duration' => $this->getDurationForHistoryStep($traderHistoryKey),
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
                    'date' => optional($getPurchaseComidityDate)->created_at?->format('Y-m-d h:i:s A'),
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
                'step' => 'contract_signed',
                'wakala_document' => [
                    'url' => $wakalaMedia?->file_url,
                    'date' => optional($wakalaMedia)->created_at?->format('Y-m-d h:i:s A'),
                ],
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
                        ->getFirstMedia($mediaMurabhaSaleCompleted)
                        ?->file_url,
                    'date' => optional($this->traderHistories
                        ->where(
                            'action',
                            $historyMurabhaSaleCompleted
                        )
                        ->first())->created_at?->format('Y-m-d h:i:s A'),
                ],
                'warranty2_document' => [
                    'url' => $this->traderOrder
                        ->getFirstMedia(TraderOrderMediaCollection::BursamTtiHoldingCertificate)
                        ?->file_url,
                    'date' => optional($historyStbCertificate)->created_at?->format('Y-m-d h:i:s A'),
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
}
