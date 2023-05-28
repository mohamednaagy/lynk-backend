<?php

namespace App\Transformers;

use App\Enums\BursamMurabhaStep;
use App\Enums\DmccMurabhaStep;
use App\Enums\FinancingOrderHistory;
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

    public function __construct(protected ?TraderOrder $traderOrder, $HistorySteps)
    {
        $this->defaultIncludes = array_merge($this->defaultIncludes, $HistorySteps);
        $this->murabhaSteps = match ($traderOrder->provider) {
            'dmcc', 'fake' => DmccMurabhaStep::class,
            'bursam' => BursamMurabhaStep::class,
            default => throw new TraderNotSupportedException()
        };
        $this->traderStepHistories = new StepHistoriesDictionary($this->traderOrder->provider, $this->traderOrder->version);
        $this->traderHistories = $traderOrder->traderHistories ?? collect();
    }

    protected array $defaultIncludes = [];

    protected array $availableIncludes = [];

    public function transform($history): array
    {
        return [];
    }

    public function includePurchasingCommodity($histories): Primitive
    {
        $stepHistoriesNode = $this->traderStepHistories->getStepOf($this->murabhaSteps::PurchasingCommodity);
        $lastHistoryOfStepNode = end($stepHistoriesNode->histories);
        $history = null;

        if (in_array($lastHistoryOfStepNode, $histories)) {
            $history = $this->getHistory($lastHistoryOfStepNode);
        }

        $certDocumentHistory = match ($this->traderOrder->provider) {
            'dmcc', 'fake' => FinancingOrderHistory::GetPtpDocument,
            'bursam' => FinancingOrderHistory::AttachTtiHoldingCertificateDocument,
        };

        return $this->primitive([
            'step' => 'commodity_purchased',
            'is_complete' => (bool) $history,
            'completed_at' => optional($history)->created_at?->format('Y-m-d h:i:s A'),
            'cert_document' => [
                'url' => $this->traderOrder
                    ->getFirstMedia(TraderOrderMediaCollection::TtiHoldingCertificate)
                    ?->file_url,
                'date' => optional($this->getHistory($certDocumentHistory))->created_at?->format('Y-m-d h:i:s A'),
            ],
            'ownership_document' => [
                'url' => $this->traderOrder
                    ->getFirstMedia(TraderOrderMediaCollection::TransferOwnershipToLender)
                    ?->file_url,
                'date' => optional($this->getHistory(FinancingOrderHistory::CreateTransferOwnershipToLenderDocument))->created_at?->format('Y-m-d h:i:s A'),
            ],
            'duration' => $this->getDurationForHistoryStep($lastHistoryOfStepNode),
        ]);
    }

    public function includeClientWakala($histories): Primitive
    {
        $stepHistoriesNode = $this->traderStepHistories->getStepOf($this->murabhaSteps::ClientWakala);
        $lastHistoryOfStepNode = end($stepHistoriesNode->histories);
        $history = null;

        if (in_array($lastHistoryOfStepNode, $histories)) {
            $history = $this->getHistory($lastHistoryOfStepNode);
        }

        return $this->primitive([
            'step' => 'client_wakala',
            'is_complete' => (bool) $history,
            'completed_at' => optional($history)->created_at?->format('Y-m-d h:i:s A'),
            'duration' => $this->getDurationForHistoryStep($lastHistoryOfStepNode),
            'signed_wakala_document' => [
                'url' => $this->getMedia(TraderOrderMediaCollection::SignedClientWakala)?->file_url,
                'date' => optional($this->getMedia(TraderOrderMediaCollection::SignedClientWakala))->created_at?->format('Y-m-d h:i:s A'),
            ],
        ]);
    }

    public function includeContractSigned($histories): Primitive
    {
        $stepHistoriesNode = $this->traderStepHistories->getStepOf($this->murabhaSteps::ContractSigned);
        $lastHistoryOfStepNode = end($stepHistoriesNode->histories);
        $history = null;

        if (in_array($lastHistoryOfStepNode, $histories)) {
            $history = $this->getHistory($lastHistoryOfStepNode);
        }

        return $this->primitive([
            'step' => 'contract_signed',
            'wakala_document' => [
                'url' => $this->getMedia(TraderOrderMediaCollection::ClientWakala)?->file_url,
                'date' => optional($this->getMedia(TraderOrderMediaCollection::ClientWakala))->created_at?->format('Y-m-d h:i:s A'),
            ],
            'is_complete' => (bool) $history,
            'completed_at' => optional($history)->created_at?->format('Y-m-d h:i:s A'),
            'duration' => $this->getDurationForHistoryStep($lastHistoryOfStepNode),
        ]);
    }

    public function includeCommoditySoldToCustomer($histories): Primitive
    {
        $stepHistoriesNode = $this->traderStepHistories->getStepOf($this->murabhaSteps::CommoditySoldToCustomer);
        $lastHistoryOfStepNode = end($stepHistoriesNode->histories);
        $history = null;

        if (in_array($lastHistoryOfStepNode, $histories)) {
            $history = $this->getHistory($lastHistoryOfStepNode);
        }

        return $this->primitive([
            'step' => 'selling_commodity_to_customer',
            'is_complete' => (bool) $history,
            'completed_at' => optional($history)->created_at?->format('Y-m-d h:i:s A'),
            'duration' => $this->getDurationForHistoryStep($lastHistoryOfStepNode),
            'document' => $this->traderOrder
                ->getFirstMedia(TraderOrderMediaCollection::SellingCommodityToCustomer)
                ?->file_url,
        ]);
    }

    public function includeMurabhaOfferIssued($histories): Primitive
    {
        $stepHistoriesNode = $this->traderStepHistories->getStepOf($this->murabhaSteps::MurabhaOfferIssued);
        $lastHistoryOfStepNode = end($stepHistoriesNode->histories);
        $history = null;

        if (in_array($lastHistoryOfStepNode, $histories)) {
            $history = $this->getHistory($lastHistoryOfStepNode);
        }

        return $this->primitive([
            'step' => 'selling_commodity_to_open_market',
            'is_complete' => (bool) $this->getHistory(FinancingOrderHistory::AttachMpoDocument),
            'completed_at' => optional($history)->created_at?->format('Y-m-d h:i:s A'),
            'mpo_document' => [
                'url' => $this->traderOrder
                    ->getFirstMedia(TraderOrderMediaCollection::MurabahaPurchaseOrder)
                    ?->file_url,
                'date' => optional($this->getHistory(FinancingOrderHistory::GetMurabahaPurchaseOfferDocument))->created_at?->format('Y-m-d h:i:s A'),
            ],
            'duration' => $this->getDurationForHistoryStep($lastHistoryOfStepNode),
        ]);
    }

    public function includeMurabahaSaleCompleted($histories): Primitive
    {
        $stepHistoriesNode = $this->traderStepHistories->getStepOf($this->murabhaSteps::MurabahaSaleCompleted);
        $lastHistoryOfStepNode = end($stepHistoriesNode->histories);
        $history = null;

        if (in_array($lastHistoryOfStepNode, $histories)) {
            $history = $this->getHistory($lastHistoryOfStepNode);
        }

        [$warrantyDocumentUrl, $warrantyDocumentDate] = match ($this->traderOrder->provider) {
            'dmcc', 'fake' => [
                $this->getMedia(TraderOrderMediaCollection::WarrantAmendmentExceptWarrantNo)?->file_url,
                $this->getHistory(FinancingOrderHistory::GetWarrantAmendmentExceptWarrantNoDocument)?->created_at?->format('Y-m-d h:i:s A'),
            ],
            'bursam' => [
                $this->getMedia(TraderOrderMediaCollection::BursamTtiHoldingCertificate)?->file_url,
                $this->getHistory(FinancingOrderHistory::GetSellingToBursaCertificate)?->created_at?->format('Y-m-d h:i:s A'),
            ],
        };

        return $this->primitive([
            'step' => 'murabha_sale_completed',
            'is_complete' => (bool) $history,
            'completed_at' => optional($history)->created_at?->format('Y-m-d h:i:s A'),
            'warranty_document' => [
                'url' => $warrantyDocumentUrl,
                'date' => $warrantyDocumentDate,
            ],
            'duration' => $this->getDurationForHistoryStep($lastHistoryOfStepNode),
        ]);
    }

    public function getDurationForHistoryStep($history)
    {
        $financingOrderStatus = $this->traderStepHistories->getStepByHistory($history)
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
        $previousStepActions = $this->traderStepHistories->getPreviousStepOf($status)
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
