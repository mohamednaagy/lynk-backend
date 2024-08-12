<?php

namespace App\Actions\Orders\Webhooks\Traits;

use App\Actions\Orders\Webhooks\Product\ProductFactory;
use App\Enums\MurabhaStep;
use App\Models\TraderOrder;
use App\Support\FinancingOrders\StepAndHistories\StepHistoriesDictionary;
use Illuminate\Database\Eloquent\Model;

trait OrderWebhooksHelper
{
    public function getTraderOrderLastHistory(TraderOrder $traderOrder): ?Model
    {
        return $traderOrder->traderHistories()->latest('id')->first();
    }

    public function getFormattedDateTime(?Model $model)
    {
        return $model?->updated_at?->clone()->tz('Asia/Riyadh')->format('Y-m-d h:i:s A');
    }

    public function getCompletedStep(TraderOrder $traderOrder)
    {
        return $this->getCompletedStepOfTrader($traderOrder->provider);
    }

    public function getDictionaryOfTraderOrder(TraderOrder $traderOrder): StepHistoriesDictionary
    {
        return new StepHistoriesDictionary($traderOrder->provider, $traderOrder->version);
    }

    public function resolveProducts(TraderOrder $traderOrder): array
    {
        $products = ProductFactory::create($traderOrder->provider);

        return $products->map($traderOrder);
    }

    public function getUiStepName(?string $step): ?string
    {
        return match ($step) {
            MurabhaStep::CommoditySoldToCustomer => MurabhaStep::BorrowerOwnershipCertificate,
            MurabhaStep::CustomerDeliveryConfirmation => MurabhaStep::MurabahaSaleCompleted,
            default => $step
        };
    }
}
