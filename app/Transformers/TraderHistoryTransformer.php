<?php

namespace App\Transformers;

use App\Enums\BursamMurabhaStep;
use App\Enums\DmccMurabhaStep;
use App\Enums\MediaCollections\TraderOrderMediaCollection;
use App\Exceptions\TraderNotSupportedException;
use App\Models\TraderOrder;
use App\Support\FinancingOrders\StepAndHistories\StepHistoriesDictionary;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use League\Fractal\Resource\Primitive;
use League\Fractal\TransformerAbstract;

class TraderHistoryTransformer extends TransformerAbstract
{
    protected string $murabhaSteps;

    protected StepHistoriesDictionary $traderStepHistories;

    protected Collection $traderHistories;

    protected array $defaultIncludes = [];

    protected array $availableIncludes = [];

    public function __construct(protected ?TraderOrder $traderOrder, $historySteps)
    {
        $this->setDefaultIncludes(array_merge($this->getDefaultIncludes(), $historySteps));
        $this->murabhaSteps = match ($traderOrder->provider) {
            'dmcc', 'fake' => DmccMurabhaStep::class,
            'bursam' => BursamMurabhaStep::class,
            default => throw new TraderNotSupportedException()
        };
        $this->traderStepHistories = new StepHistoriesDictionary($this->traderOrder->provider, $this->traderOrder->version);
        $this->traderHistories = $traderOrder->traderHistories ?? collect();
    }

    public function transform($historiesActions): array
    {
        return [];
    }

    public function getCurrentLastHistoryAndLastHistoryOfStep($historiesActions, $step)
    {
        $stepHistoriesNode = $this->traderStepHistories->getStepOf($step);
        $lastHistoryOfStepNode = end($stepHistoriesNode->histories);
        $history = null;

        if (in_array($lastHistoryOfStepNode, $historiesActions)) {
            $history = $this->getHistory($lastHistoryOfStepNode);
        }

        return [$history, $lastHistoryOfStepNode];
    }

    public function includePurchasingCommodity($historiesActions): Primitive
    {
        [$history, $lastHistoryOfStepNode] = $this->getCurrentLastHistoryAndLastHistoryOfStep($historiesActions, $this->murabhaSteps::PurchasingCommodity);

        $certDocumentMediaFile = $this->getMedia(TraderOrderMediaCollection::TtiHoldingCertificate);
        $ownershipDocumentMediaFile = $this->getMedia(TraderOrderMediaCollection::TransferOwnershipToLender);

        return $this->primitive([
            'step' => 'commodity_purchased',
            'is_complete' => (bool) $history,
            'completed_at' => optional($history)->created_at?->format('Y-m-d h:i:s A'),
            'cert_document' => [
                'url' => $certDocumentMediaFile?->file_url,
                'date' => $certDocumentMediaFile?->created_at?->format('Y-m-d h:i:s A'),
            ],
            'ownership_document' => [
                'url' => $ownershipDocumentMediaFile?->file_url,
                'date' => $ownershipDocumentMediaFile?->created_at?->format('Y-m-d h:i:s A'),
            ],
            'duration' => $this->getDurationForHistoryStep($lastHistoryOfStepNode),
        ]);
    }

    public function includeClientWakala($historiesActions): Primitive
    {
        [$history, $lastHistoryOfStepNode] = $this->getCurrentLastHistoryAndLastHistoryOfStep(
            $historiesActions, $this->murabhaSteps::ClientWakala
        );

        $signedWakalaDocumentMediaFile = $this->getMedia(TraderOrderMediaCollection::SignedClientWakala);

        return $this->primitive([
            'step' => 'client_wakala',
            'is_complete' => (bool) $history,
            'completed_at' => $history?->created_at?->format('Y-m-d h:i:s A'),
            'signed_wakala_document' => [
                'url' => $signedWakalaDocumentMediaFile?->file_url,
                'date' => $signedWakalaDocumentMediaFile?->created_at?->format('Y-m-d h:i:s A'),
            ],
            'duration' => $this->getDurationForHistoryStep($lastHistoryOfStepNode),
        ]);
    }

    public function includeContractSigned($historiesActions): Primitive
    {
        [$history, $lastHistoryOfStepNode] = $this->getCurrentLastHistoryAndLastHistoryOfStep(
            $historiesActions, $this->murabhaSteps::ContractSigned
        );

        $wakalaDocumentMediaFile = $this->getMedia(TraderOrderMediaCollection::ClientWakala);

        return $this->primitive([
            'step' => 'contract_signed',
            'is_complete' => (bool) $history,
            'completed_at' => optional($history)->created_at?->format('Y-m-d h:i:s A'),
            'wakala_document' => [
                'url' => $wakalaDocumentMediaFile?->file_url,
                'date' => $wakalaDocumentMediaFile?->created_at?->format('Y-m-d h:i:s A'),
            ],
            'duration' => $this->getDurationForHistoryStep($lastHistoryOfStepNode),
        ]);
    }

    public function includeCommoditySoldToCustomer($historiesActions): Primitive
    {
        [$history, $lastHistoryOfStepNode] = $this->getCurrentLastHistoryAndLastHistoryOfStep(
            $historiesActions, $this->murabhaSteps::CommoditySoldToCustomer
        );

        $documentMediaFile = $this->getMedia(TraderOrderMediaCollection::SellingCommodityToCustomer);

        return $this->primitive([
            'step' => 'selling_commodity_to_customer',
            'is_complete' => (bool) $history,
            'completed_at' => $history?->created_at?->format('Y-m-d h:i:s A'),
            'borrower_document' => [
                'url' => $documentMediaFile?->file_url,
                'date' => $documentMediaFile?->created_at?->format('Y-m-d h:i:s A'),
            ],
            'duration' => $this->getDurationForHistoryStep($lastHistoryOfStepNode),
        ]);
    }

    public function includeMurabhaOfferIssued($historiesActions): Primitive
    {
        [$history, $lastHistoryOfStepNode] = $this->getCurrentLastHistoryAndLastHistoryOfStep(
            $historiesActions, $this->murabhaSteps::MurabhaOfferIssued
        );

        $mpoDocumentMediaFile = $this->getMedia(TraderOrderMediaCollection::MurabahaPurchaseOrder);

        return $this->primitive([
            'step' => 'selling_commodity_to_open_market',
            'is_complete' => (bool) $history,
            'completed_at' => $history?->created_at?->format('Y-m-d h:i:s A'),
            'mpo_document' => [
                'url' => $mpoDocumentMediaFile?->file_url,
                'date' => $mpoDocumentMediaFile?->created_at?->format('Y-m-d h:i:s A'),
            ],
            'duration' => $this->getDurationForHistoryStep($lastHistoryOfStepNode),
        ]);
    }

    public function includeMurabahaSaleCompleted($historiesActions): Primitive
    {
        [$history, $lastHistoryOfStepNode] = $this->getCurrentLastHistoryAndLastHistoryOfStep(
            $historiesActions, $this->murabhaSteps::MurabahaSaleCompleted
        );

        $warrantyDocumentMediaFile = match ($this->traderOrder->provider) {
            'dmcc', 'fake' => $this->getMedia(TraderOrderMediaCollection::WarrantAmendmentExceptWarrantNo),
            'bursam' => $this->getMedia(TraderOrderMediaCollection::BursamTtiHoldingCertificate),
        };

        return $this->primitive([
            'step' => 'murabha_sale_completed',
            'is_complete' => (bool) $history,
            'completed_at' => optional($history)->created_at?->format('Y-m-d h:i:s A'),
            'warranty_document' => [
                'url' => $warrantyDocumentMediaFile?->file_url,
                'date' => $warrantyDocumentMediaFile?->created_at?->format('Y-m-d h:i:s A'),
            ],
            'duration' => $this->getDurationForHistoryStep($lastHistoryOfStepNode),
        ]);
    }

    public function getDurationForHistoryStep($history)
    {
        $CurrentStep = $this->traderStepHistories->getStepByHistory($history)
            ?->step;

        if (blank($CurrentStep)) {
            return null;
        }

        $previousAction = $this->getLatestTraderHistoryOfPreviousStep($CurrentStep);
        $latestAction = $this->getLatestTraderHistoryOfStep($CurrentStep);

        if ($previousAction?->created_at && $latestAction?->created_at) {
            $diffTime = $previousAction->created_at->diffForHumans(
                $latestAction->created_at, [
                    'parts' => 3,
                    'join' => true,
                ]);

            $ignoredWords = ['ago', 'before', 'after', 'منذ', 'قبل'];

            return Str::remove($ignoredWords, $diffTime);
        }

        return null;
    }

    private function getLatestTraderHistoryOfPreviousStep($step)
    {
        $previousStepActions = $this->traderStepHistories->getPreviousStepOf($step)
            ?->histories;

        return blank($previousStepActions)
            ? null
            : $this->traderHistories->whereIn('action', $previousStepActions)
                ->sortBy('updated_at', descending: true)
                ->first();
    }

    private function getLatestTraderHistoryOfStep($step)
    {
        $stepActions = (new StepHistoriesDictionary($this->traderOrder->provider, $this->traderOrder->version))
            ->getStepOf($step)
            ?->histories;

        return $this->traderHistories->whereIn('action', $stepActions ?? [])
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
