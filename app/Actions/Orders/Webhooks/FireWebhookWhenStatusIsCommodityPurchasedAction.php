<?php

namespace App\Actions\Orders\Webhooks;

use App\Actions\Contracts\Orders\Webhooks\FireWebhookWhenStatusIsCommodityPurchased;
use App\Actions\Orders\Webhooks\Traits\OrderWebhooksHelper;
use App\Enums\MediaCollections\TraderOrderMediaCollection;
use App\Enums\MurabhaStep;
use App\Enums\WebhookType;
use App\Models\FinancingOrder;
use App\Models\TraderOrder;
use App\Support\Webhooks\Facades\WebhookEvent;

class FireWebhookWhenStatusIsCommodityPurchasedAction implements FireWebhookWhenStatusIsCommodityPurchased
{
    use OrderWebhooksHelper;

    public function handle(FinancingOrder $financingOrder, TraderOrder $traderOrder): void
    {
        if (is_null($traderOrder->products)) {
            return;
        }

        $certDocumentMediaFile = get_media_of_model($traderOrder, TraderOrderMediaCollection::TtiHoldingCertificate);
        $ownershipDocumentMediaFile = get_media_of_model($traderOrder, TraderOrderMediaCollection::TransferOwnershipToLender);
        $lastHistory = $this->getTraderOrderLastHistory($traderOrder);
        $lastCompletedStep = $this->getCompletedStep($traderOrder);
        $nextStep = $this->getDictionaryOfTraderOrder($traderOrder)
            ->getNextStepOf($lastCompletedStep);

        WebhookEvent::fire($financingOrder->company, WebhookType::OrderUpdates, [
            'order_id' => $financingOrder->id,
            'order_status' => [
                'value' => $financingOrder->status->value,
                'label' => $financingOrder->status->description,
            ],
            'trading_information' => [
                'trading_id' => $traderOrder->id,
                'trading_reference' => $traderOrder->reference,
                'current_trading_step' => $this->getUiStepName($nextStep?->step),
                'completed_murabaha_step' => $this->getUiStepName($lastCompletedStep),
                'products' => $this->resolveProducts($traderOrder),
                'cert_document_url' => get_file_url($certDocumentMediaFile),
                'ownership_document_url' => get_file_url($ownershipDocumentMediaFile),
            ],
            'updated_at' => $this->getFormattedDateTime($lastHistory),
        ]);
    }

    protected function getCompletedStepOfTrader($provider): string
    {
        return MurabhaStep::PurchasingCommodity;
    }
}
