<?php

namespace App\Actions\Orders;

use App\Actions\Contracts\Orders\FireWebhookWhenStatusIsCommoditySoldToCustomer;
use App\Enums\MediaCollections\TraderOrderMediaCollection;
use App\Enums\WebhookType;
use App\Models\FinancingOrder;
use App\Models\TraderOrder;
use App\Support\FinancingOrders\StepAndHistories\StepHistoriesDictionary;
use App\Support\Webhooks\Facades\WebhookEvent;

class FireWebhookWhenStatusIsCommoditySoldToCustomerAction implements FireWebhookWhenStatusIsCommoditySoldToCustomer
{
    public function handle(FinancingOrder $financingOrder, TraderOrder $traderOrder): void
    {
        $documentMediaFile = get_media_of_model($traderOrder, TraderOrderMediaCollection::SellingCommodityToCustomer);
        $nextStepOfMurabahaStepCompleted = (new StepHistoriesDictionary($traderOrder->provider, $traderOrder->version))
            ->getNextStepOf($traderOrder->currentStep);

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
                    'current_trading_status' => $nextStepOfMurabahaStepCompleted->step,
                    'completed_murabaha_step' => $traderOrder->currentStep,
                    'borrower_document_url' => get_file_url($documentMediaFile),
                ],
            ]
        );
    }
}
