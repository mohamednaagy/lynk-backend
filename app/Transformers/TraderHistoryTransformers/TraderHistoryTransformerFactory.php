<?php

namespace App\Transformers\TraderHistoryTransformers;

use App\Enums\FinancingOrderTypeEnum;
use App\Models\TraderOrder;

class TraderHistoryTransformerFactory
{
    public static function make(TraderOrder $traderOrder, $historySteps): AbstractTraderHistoryTransformer
    {
        return match ($traderOrder->order->type->value) {
            FinancingOrderTypeEnum::NormalLending => new NormalLendingTraderHistoryTransformer($traderOrder, $historySteps),
            FinancingOrderTypeEnum::SpecialPurposeVehicle => new SpecialPurposeVehicleTraderHistoryTransformer($traderOrder, $historySteps),
            FinancingOrderTypeEnum::TimeDeposit => new TimeDepositTraderHistoryTransformer($traderOrder, $historySteps),
            default => new NormalLendingTraderHistoryTransformer($traderOrder, $historySteps)
        };
    }
}
