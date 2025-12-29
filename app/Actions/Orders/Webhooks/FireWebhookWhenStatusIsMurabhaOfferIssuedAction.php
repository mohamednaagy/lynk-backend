<?php

namespace App\Actions\Orders\Webhooks;

use App\Actions\Contracts\Orders\Webhooks\FireWebhookWhenStatusIsMurabhaOfferIssued;
use App\Enums\WebhookType;
use App\Models\FinancingOrder;
use App\Support\Webhooks\Facades\WebhookEvent;

class FireWebhookWhenStatusIsMurabhaOfferIssuedAction implements FireWebhookWhenStatusIsMurabhaOfferIssued
{
    public function handle(FinancingOrder $financingOrder): void
    {

        $lender = $financingOrder->lender()->withTrashed()->first();
        WebhookEvent::fire($lender, WebhookType::OrderUpdates, [
            'order_id' => $financingOrder->id,
            'order_status' => [
                'value' => $financingOrder->status->value,
                'label' => $financingOrder->status->description,
            ],
        ]);
    }
}
