<?php

namespace App\Transformers;

use App\Enums\DocumentType;
use App\Enums\FinancingOrderHistory;
use App\Enums\MediaCollections\TraderOrderMediaCollection;
use App\Enums\MurabhaStep;
use App\Enums\Trader as TraderEnum;
use App\Models\TraderOrder;
use App\Services\TraderOrder\StepDurationService;
use App\Support\FinancingOrders\StepAndHistories\StepHistoriesDictionary;
use App\Support\Traders\Facades\Trader;
use Illuminate\Support\Collection;
use League\Fractal\Resource\Primitive;
use League\Fractal\TransformerAbstract;

class TraderHistoryTransformer extends TransformerAbstract
{
    protected StepHistoriesDictionary $traderStepHistories;

    protected StepDurationService $stepDurationService;

    protected Collection $traderHistories;

    protected array $defaultIncludes = [];

    protected array $availableIncludes = [];

    public function __construct(protected ?TraderOrder $traderOrder, $historySteps)
    {
        $this->setDefaultIncludes(array_merge($this->getDefaultIncludes(), $historySteps));
        $this->traderStepHistories = new StepHistoriesDictionary($this->traderOrder->provider, $this->traderOrder->version, $this->traderOrder->contract_signed_type);
        $this->traderHistories = $traderOrder->traderHistories ?? collect();
        $this->traderOrder->traderOrderDuration;
        $this->stepDurationService = app(StepDurationService::class);
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
        [$history, $lastHistoryOfStepNode] = $this->getCurrentLastHistoryAndLastHistoryOfStep($historiesActions, MurabhaStep::PurchasingCommodity);

        $data = [
            'step' => MurabhaStep::PurchasingCommodity,
            'is_complete' => (bool) $history,
            'completed_at' => optional($history) ? saudi_now('Y-m-d h:i:s A', optional($history)->created_at) : null,
            'ownership_document' => [
                'url' => formatMediaUrl(route('api.v1.admins.generate', [
                    'document_type' => DocumentType::TRANSFER_OWNERSHIP_TO_LENDER,
                    'context' => [
                        'trader_order_id' => $this->traderOrder->id,
                    ],
                ])),
                'date' => $history ? saudi_now('Y-m-d h:i:s A', $history->created_at) : null,
            ],
            'duration' => $this->stepDurationService->getStepDuration($this->traderOrder, MurabhaStep::PurchasingCommodity),
        ];

        if ($this->traderOrder->provider === TraderEnum::Bursam) {
            $data['cert_document'] = [
                'url' => formatMediaUrl(route('api.v1.admins.generate', [
                    'document_type' => DocumentType::BURSAM_BID_CERTIFICATE,
                    'context' => [
                        'trader_order_id' => $this->traderOrder->id,
                    ],
                ])),
                'date' => $history ? saudi_now('Y-m-d h:i:s A', $history->created_at) : null,
            ];
        }

        return $this->primitive($data);
    }

    public function includeClientWakala($historiesActions): Primitive
    {
        [$history, $lastHistoryOfStepNode] = $this->getCurrentLastHistoryAndLastHistoryOfStep(
            $historiesActions,
            MurabhaStep::ClientWakala
        );

        $signedWakalaDocumentMediaFile = $this->getMedia(TraderOrderMediaCollection::SignedClientWakala);

        return $this->primitive([
            'step' => MurabhaStep::ClientWakala,
            'is_complete' => (bool) $history,
            'completed_at' => $history ? saudi_now('Y-m-d h:i:s A', $history->created_at) : null,
            'is_deliverable' => $this->traderOrder->isDeliverable(),
            'client_wakala_message' => Trader::driver($this->traderOrder->provider, $this->traderOrder->version)->clientWakalaMessage($this->traderOrder),
            'signed_wakala_document' => [
                'url' => $signedWakalaDocumentMediaFile?->file_url,
                'date' => $signedWakalaDocumentMediaFile ? saudi_now('Y-m-d h:i:s A', $signedWakalaDocumentMediaFile->created_at) : null,
            ],
            'duration' => $this->stepDurationService->getStepDuration($this->traderOrder, MurabhaStep::ClientWakala),
        ]);
    }

    public function includeContractSigned($historiesActions): Primitive
    {
        [$history, $lastHistoryOfStepNode] = $this->getCurrentLastHistoryAndLastHistoryOfStep(
            $historiesActions,
            MurabhaStep::ContractSigned
        );
        $transferOwnershipToLenderDocumentHistory = $this->traderOrder->traderHistories()
            ->where('action', FinancingOrderHistory::CreateTransferOwnershipToLenderDocument)
            ->first();

        $data = [
            'step' => MurabhaStep::ContractSigned,
            'is_complete' => (bool) $history,
            'completed_at' => $history ? saudi_now('Y-m-d h:i:s A', $history->created_at) : null,
            'is_deliverable' => $this->traderOrder->isDeliverable(),
            'contract_signed_message' => Trader::driver($this->traderOrder->provider, $this->traderOrder->version)->contractSignedMessage($this->traderOrder),
            'wakala_document' => [
                'url' => formatMediaUrl(route('api.v1.admins.generate', [
                    'document_type' => DocumentType::CLIENT_WAKALA,
                    'context' => [
                        'trader_order_id' => $this->traderOrder->id,
                    ],
                ])),
                'date' => $transferOwnershipToLenderDocumentHistory ? saudi_now('Y-m-d h:i:s A', $transferOwnershipToLenderDocumentHistory->created_at) : null,
            ],
            'duration' => $this->stepDurationService->getStepDuration($this->traderOrder, MurabhaStep::ContractSigned),
        ];

        if ($this->traderOrder->isVersion('v2')) {
            unset($data['is_deliverable']);
        }

        return $this->primitive($data);
    }

    public function includeCommoditySoldToCustomer($historiesActions): Primitive
    {
        [$history, $lastHistoryOfStepNode] = $this->getCurrentLastHistoryAndLastHistoryOfStep(
            $historiesActions,
            MurabhaStep::CommoditySoldToCustomer
        );

        return $this->primitive([
            'step' => MurabhaStep::CommoditySoldToCustomer,
            'is_complete' => (bool) $history,
            'completed_at' => $history ? saudi_now('Y-m-d h:i:s A', $history->created_at) : null,
            'borrower_document' => [
                'url' => formatMediaUrl(route('api.v1.admins.generate', [
                    'document_type' => DocumentType::SELLING_COMMODITY_TO_CUSTOMER,
                    'context' => [
                        'trader_order_id' => $this->traderOrder->id,
                    ],
                ])),
                'date' => $history ? saudi_now('Y-m-d h:i:s A', $history->created_at) : null,
            ],
            'duration' => $this->stepDurationService->getStepDuration($this->traderOrder, MurabhaStep::CommoditySoldToCustomer),
        ]);
    }

    public function includeMurabhaOfferIssued($historiesActions): Primitive
    {
        [$history, $lastHistoryOfStepNode] = $this->getCurrentLastHistoryAndLastHistoryOfStep(
            $historiesActions,
            MurabhaStep::MurabhaOfferIssued
        );

        return $this->primitive([
            'step' => MurabhaStep::SellingCommodityToOpenMarket,
            'is_complete' => (bool) $history,
            'completed_at' => $history ? saudi_now('Y-m-d h:i:s A', $history->created_at) : null,
            'mpo_document' => [
                'url' => formatMediaUrl(route('api.v1.admins.generate', [
                    'document_type' => DocumentType::getSellingPledgeCertificateType($this->traderOrder->provider),
                    'context' => [
                        'trader_order_id' => $this->traderOrder->id,
                    ],
                ])),
                'date' => $history ? saudi_now('Y-m-d h:i:s A', $history->created_at) : null,
            ],
            'duration' => $this->stepDurationService->getStepDuration($this->traderOrder, MurabhaStep::MurabhaOfferIssued),
        ]);
    }

    public function includeMurabahaSaleCompleted($historiesActions): Primitive
    {
        [$history, $lastHistoryOfStepNode] = $this->getCurrentLastHistoryAndLastHistoryOfStep(
            $historiesActions,
            MurabhaStep::MurabahaSaleCompleted
        );

        $data = [
            'step' => MurabhaStep::MurabahaSaleCompleted,
            'is_complete' => (bool) $history,
            'completed_at' => optional($history)->created_at?->clone()->tz('Asia/Riyadh')->format('Y-m-d h:i:s A'),
            'warranty_document' => [
                'url' => formatMediaUrl(route('api.v1.admins.generate', [
                    'document_type' => DocumentType::getSellingPledgeCertificateType($this->traderOrder->provider),
                    'context' => [
                        'trader_order_id' => $this->traderOrder->id,
                    ],
                ])),
                'date' => $history ? saudi_now('Y-m-d h:i:s A', $history->created_at) : null,
            ],
            'duration' => $this->stepDurationService->getStepDuration($this->traderOrder, MurabhaStep::MurabahaSaleCompleted),
        ];

        if ($this->traderOrder->provider === TraderEnum::Lynk) {
            $attachedSellConfirmationDocument = $this->traderOrder->traderHistories()->where('action', FinancingOrderHistory::AttachSellConfirmationDocument)->first();
            if ($attachedSellConfirmationDocument) {
                $data['sell_confirmation_document'] = [
                    'url' => formatMediaUrl(route('api.v1.admins.generate', [
                        'document_type' => DocumentType::SELL_CONFIRMATION_DOCUMENT,
                        'context' => [
                            'trader_order_id' => $this->traderOrder->id,
                        ],
                    ])),
                    'date' => $attachedSellConfirmationDocument ? saudi_now('Y-m-d h:i:s A', $attachedSellConfirmationDocument->created_at) : null,
                ];
            }
        }

        return $this->primitive($data);
    }

    public function getHistory($history)
    {
        return $this->traderHistories->where('action', $history)->first();
    }

    public function getMedia($media)
    {
        return $this->traderOrder->getFirstMedia($media);
    }

    public function includeCustomerDeliveryConfirmation($historiesActions): Primitive
    {
        // Get the step histories for the CustomerDeliveryConfirmation step.
        $stepHistoriesNode = $this->traderStepHistories->getStepOf(MurabhaStep::CustomerDeliveryConfirmation);

        // Retrieve the first history action associated with this step in trader history.
        $history = $this->traderOrder
            ->getOrderHistoryAction($stepHistoriesNode->histories)
            ->first();

        // Determine the last history action node. If no history found, use the last node in the step histories.
        $lastHistoryOfStepNode = $history ? $history->action : end($stepHistoriesNode->histories);

        return $this->primitive([
            'step' => MurabhaStep::CustomerDeliveryConfirmation,
            'is_complete' => (bool) $history,
            'completed_at' => $history?->created_at?->clone()->tz('Asia/Riyadh')->format('Y-m-d h:i:s A'),
            'delivery_details' => $this->traderOrder->getCustomerDeliveryStatusAndMessage(),
            'duration' => $this->stepDurationService->getStepDuration($this->traderOrder, MurabhaStep::CustomerDeliveryConfirmation),
        ]);
    }
}
