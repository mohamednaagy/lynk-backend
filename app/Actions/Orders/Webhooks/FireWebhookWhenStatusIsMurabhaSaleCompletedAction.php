<?php

namespace App\Actions\Orders\Webhooks;

use App\Actions\Contracts\Orders\Webhooks\FireWebhookWhenStatusIsMurabhaSaleCompleted;
use App\Actions\Orders\Webhooks\Traits\OrderWebhooksHelper;
use App\Enums\BursamMurabhaStep;
use App\Enums\DmccMurabhaStep;
use App\Enums\MediaCollections\TraderOrderMediaCollection;
use App\Enums\Trader;
use App\Enums\WebhookType;
use App\Models\FinancingOrder;
use App\Models\TraderOrder;
use App\Support\Webhooks\Facades\WebhookEvent;

class FireWebhookWhenStatusIsMurabhaSaleCompletedAction implements FireWebhookWhenStatusIsMurabhaSaleCompleted
{
    use OrderWebhooksHelper;

    public function handle(FinancingOrder $financingOrder, TraderOrder $traderOrder): void
    {
        $warrantyMediaCollection = match ($traderOrder->provider) {
            'dmcc', 'fake' => TraderOrderMediaCollection::WarrantAmendmentExceptWarrantNo,
            'bursam' => TraderOrderMediaCollection::BursamTtiHoldingCertificate,
        };

        $documentMediaFile = get_media_of_model($traderOrder, $warrantyMediaCollection);
        $wakalaDocumentMediaFile = get_media_of_model($traderOrder, TraderOrderMediaCollection::ClientWakala);
        $lastHistory = $this->getTraderOrderLastHistory($traderOrder);
        $lastCompletedStep = $this->getCompletedStep($traderOrder);

        WebhookEvent::fire($financingOrder->company, WebhookType::OrderUpdates, [
            'order_id' => $financingOrder->id,
            'order_status' => [
                'value' => $financingOrder->status->value,
                'label' => $financingOrder->status->description,
            ],
            'trading_information' => [
                'trading_id' => $traderOrder->id,
                'trading_reference' => $traderOrder->reference,
                'current_trading_step' => 'completed',
                'completed_murabaha_step' => $lastCompletedStep?->step,
                'signed_wakala_document_url' => get_file_url($wakalaDocumentMediaFile),
                'warranty_document_url' => get_file_url($documentMediaFile),
            ],
            'updated_at' => $this->getFormattedDateTime($lastHistory),
        ]);
    }

    protected function getCompletedStepOfTrader($provider)
    {
        return match ($provider) {
            Trader::Bursam => BursamMurabhaStep::MurabahaSaleCompleted,
            Trader::Dmcc => DmccMurabhaStep::MurabahaSaleCompleted,
            Trader::FakeDmcc => DmccMurabhaStep::MurabahaSaleCompleted,
        };
    }
}
