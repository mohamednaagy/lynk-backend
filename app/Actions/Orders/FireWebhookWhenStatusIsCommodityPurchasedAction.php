<?php

namespace App\Actions\Orders;

use App\Actions\Contracts\Orders\FireWebhookWhenStatusIsCommodityPurchased;
use App\Enums\MediaCollections\TraderOrderMediaCollection;
use App\Enums\WebhookType;
use App\Models\FinancingOrder;
use App\Models\TraderOrder;
use App\Support\DataTransferObjects\CommodityProductDto;
use App\Support\FinancingOrders\StepAndHistories\StepHistoriesDictionary;
use App\Support\Webhooks\Facades\WebhookEvent;

class FireWebhookWhenStatusIsCommodityPurchasedAction implements FireWebhookWhenStatusIsCommodityPurchased
{
    public function handle(FinancingOrder $financingOrder, TraderOrder $traderOrder): void
    {
        if (is_null($traderOrder->products)) {
            return;
        }

        $certDocumentMediaFile = get_media_of_model($traderOrder, TraderOrderMediaCollection::TtiHoldingCertificate);
        $ownershipDocumentMediaFile = get_media_of_model($traderOrder, TraderOrderMediaCollection::TransferOwnershipToLender);
        $nextStepOfMurabahaStepCompleted = (new StepHistoriesDictionary($traderOrder->provider, $traderOrder->version))
            ->getNextStepOf($traderOrder->currentStep);

        WebhookEvent::fire($financingOrder->company, WebhookType::OrderUpdates, [
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
                'products' => $this->resolveProducts($traderOrder),
                'cert_document_url' => get_file_url($certDocumentMediaFile),
                'ownership_document_url' => get_file_url($ownershipDocumentMediaFile),
            ],
        ]);
    }

    public function resolveProducts(TraderOrder $traderOrder): array
    {
        return collect($traderOrder->products)->map(function ($product) {
            $productDto = CommodityProductDto::fromArray($product);

            return [
                'product_description' => $productDto->getProduct(),
                'product_volume_unit' => $productDto->getUom(),
                'product_volume' => $productDto->getQuantity(),
                'product_value' => $productDto->getAmount(),
                'currency' => $productDto->getCurrency(),
            ];
        })
            ->toArray();
    }
}
