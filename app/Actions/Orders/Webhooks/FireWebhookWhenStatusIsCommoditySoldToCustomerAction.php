<?php

namespace App\Actions\Orders\Webhooks;

use App\Actions\Contracts\Orders\Webhooks\FireWebhookWhenStatusIsCommoditySoldToCustomer;
use App\Actions\Orders\Webhooks\Traits\OrderWebhooksHelper;
use App\Enums\MediaCollections\TraderOrderMediaCollection;
use App\Enums\WebhookType;
use App\Models\FinancingOrder;
use App\Models\TraderOrder;
use App\Support\Webhooks\Facades\WebhookEvent;

class FireWebhookWhenStatusIsCommoditySoldToCustomerAction implements FireWebhookWhenStatusIsCommoditySoldToCustomer
{
    use OrderWebhooksHelper;

    public function handle(FinancingOrder $financingOrder, TraderOrder $traderOrder): void
    {
        $documentMediaFile = get_media_of_model($traderOrder, TraderOrderMediaCollection::SellingCommodityToCustomer);
        $lastHistory = $this->getTraderOrderLastHistory($traderOrder);
        $currentStep = $this->getDictionaryOfTraderOrder($traderOrder)
            ->getStepByHistory($lastHistory->action);
        $nextStep = $this->getDictionaryOfTraderOrder($traderOrder)
            ->getNextStepOf($currentStep->step);

        WebhookEvent::fire(
            $financingOrder->company,
            WebhookType::OrderUpdates,
            [
                'order_id' => $financingOrder->id,
                'order_status' => [
                    'value' => $financingOrder->status->value,
                    'label' => $financingOrder->status->description,
                ],
                'trading_information' => [
                    'trading_id' => $traderOrder->id,
                    'trading_reference' => $traderOrder->reference,
                    'current_trading_step' => $nextStep?->step,
                    'completed_murabaha_step' => $currentStep?->step,
                    'borrower_document_url' => get_file_url($documentMediaFile),
                ],
                'updated_at' => $this->getFormattedDateTime($lastHistory),
            ]
        );
    }
}
