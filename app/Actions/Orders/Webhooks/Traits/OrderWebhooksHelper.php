<?php

namespace App\Actions\Orders\Webhooks\Traits;

use App\Models\TraderOrder;
use App\Support\DataTransferObjects\CommodityProductDto;
use App\Support\FinancingOrders\StepAndHistories\StepHistoriesDictionary;
use App\Support\FinancingOrders\StepAndHistories\StepHistoriesDictionaryNode;
use Illuminate\Database\Eloquent\Model;

trait OrderWebhooksHelper
{
    public function getTraderOrderLastHistory(TraderOrder $traderOrder): Model|null
    {
        return $traderOrder->traderHistories()->latest('id')->first();
    }

    public function getFormattedDateTime(?Model $model)
    {
        return $model?->updated_at?->clone()->tz('Asia/Riyadh')->format('Y-m-d h:i:s A');
    }

    public function getNextStepOfCurrentStep(TraderOrder $traderOrder): ?StepHistoriesDictionaryNode
    {
        return (new StepHistoriesDictionary($traderOrder->provider, $traderOrder->version))
            ->getNextStepOf($traderOrder->currentStep);
    }

    public function getLastStepBeforeCancelling(TraderOrder $traderOrder): ?string
    {
        $dictionary = new StepHistoriesDictionary($traderOrder->provider, $traderOrder->version);
        $previousStep = $dictionary->getPreviousStepOf($traderOrder->currentStep);
        $histories = $traderOrder->traderHistories;

        foreach ($histories as $history) {
            if (end($previousStep->histories) == $history->action) {
                return $previousStep->step;
            }
            $previousStep = $dictionary->getPreviousStepOf($previousStep->step);
        }

        return null;
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
