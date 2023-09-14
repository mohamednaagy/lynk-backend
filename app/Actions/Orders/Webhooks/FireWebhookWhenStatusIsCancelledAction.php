<?php

namespace App\Actions\Orders\Webhooks;

use App\Actions\Contracts\Orders\Webhooks\FireWebhookWhenStatusIsCancelled;
use App\Actions\Orders\Webhooks\Traits\OrderWebhooksHelper;
use App\Enums\MediaCollections\TraderOrderMediaCollection;
use App\Enums\MurabhaStep;
use App\Enums\WebhookType;
use App\Models\TraderOrder;
use App\Support\Webhooks\Facades\WebhookEvent;

class FireWebhookWhenStatusIsCancelledAction implements FireWebhookWhenStatusIsCancelled
{
    use OrderWebhooksHelper;

    public function handle(TraderOrder $traderOrder): void
    {
        $financingOrder = $traderOrder->order;
        $warrantyMediaCollection = match ($traderOrder->provider) {
            'dmcc', 'fake' => TraderOrderMediaCollection::WarrantAmendmentExceptWarrantNo,
            'bursam' => TraderOrderMediaCollection::BursamTtiHoldingCertificate,
        };

        $documentMediaFile = get_media_of_model($traderOrder, $warrantyMediaCollection);
        $lastCompletedStep = $this->getDictionaryOfTraderOrder($traderOrder)->getLastCompletedStepOf($traderOrder);

        WebhookEvent::fire($financingOrder->company, WebhookType::OrderUpdates, [
            'order_id' => $financingOrder->id,
            'order_status' => [
                'value' => $financingOrder->status->value,
                'label' => $financingOrder->status->description,
            ],
            'trading_information' => [
                'trading_id' => $traderOrder->id,
                'trading_reference' => $traderOrder->reference,
                'current_trading_step' => 'cancelled',
                'completed_murabaha_step' => $this->getUiStepName($lastCompletedStep?->step),
                'warranty_document_url' => get_file_url($documentMediaFile),
            ],
            'updated_at' => $this->getFormattedDateTime($traderOrder),
        ]);
    }

    protected function getCompletedStepOfTrader($provider)
    {
        return MurabhaStep::MurabahaSaleCompleted;
    }
}
