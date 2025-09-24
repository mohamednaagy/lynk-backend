<?php

namespace App\Actions\Orders\Webhooks;

use App\Actions\Contracts\Orders\Webhooks\FireWebhookWhenStatusIsCancelled;
use App\Actions\Orders\Webhooks\Traits\OrderWebhooksHelper;
use App\Enums\DocumentType;
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

        $lastCompletedStep = $this->getDictionaryOfTraderOrder($traderOrder)->getLastCompletedStepOf($traderOrder);
        $company = $financingOrder->company()->withTrashed()->first();
        WebhookEvent::fire($company, WebhookType::OrderUpdates, [
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
                'warranty_document_url' => formatMediaUrl(route('api.v1.admins.generate', [
                    'document_type' => DocumentType::getSellingPledgeCertificateType($traderOrder->provider),
                    'context' => [
                        'trader_order_id' => $traderOrder->id,
                    ],
                ])),
            ],
            'updated_at' => $this->getFormattedDateTime($traderOrder),
        ]);

    }

    protected function getCompletedStepOfTrader($provider)
    {
        return MurabhaStep::MurabahaSaleCompleted;
    }
}
