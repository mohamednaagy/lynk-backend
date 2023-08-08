<?php

namespace App\Actions\Orders;

use App\Actions\Contracts\Orders\FireWebhookWhenStatusIsCancelled;
use App\Enums\MediaCollections\TraderOrderMediaCollection;
use App\Enums\WebhookType;
use App\Models\TraderOrder;
use App\Support\Webhooks\Facades\WebhookEvent;

class FireWebhookWhenStatusIsCancelledAction implements FireWebhookWhenStatusIsCancelled
{
    public function handle(TraderOrder $traderOrder): void
    {
        $financingOrder = $traderOrder->order;
        $warrantyMediaCollection = match ($traderOrder->provider) {
            'dmcc', 'fake' => TraderOrderMediaCollection::WarrantAmendmentExceptWarrantNo,
            'bursam' => TraderOrderMediaCollection::BursamTtiHoldingCertificate,
        };

        $documentMediaFile = get_media_of_model($traderOrder, $warrantyMediaCollection);

        WebhookEvent::fire($financingOrder->company, WebhookType::OrderUpdates, [
            'order_id' => $financingOrder->id,
            'order_status' => [
                'value' => $financingOrder->status->value,
                'label' => $financingOrder->status->description,
            ],
            'trading_information' => [
                'trading_id' => $traderOrder->id,
                'trading_reference' => $traderOrder->reference,
                'current_trading_status' => 'Cancelled',
                'completed_murabaha_step' => $traderOrder->currentStep,
                'warranty_document_url' => get_file_url($documentMediaFile),
            ],
        ]);
    }
}
