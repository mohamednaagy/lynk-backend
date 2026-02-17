<?php

namespace App\Transformers\TraderHistoryTransformers;

use App\Enums\MurabhaStep;
use App\Support\Traders\Facades\Trader;
use League\Fractal\Resource\Primitive;

class TimeDepositTraderHistoryTransformer extends AbstractTraderHistoryTransformer
{
    public function includeContractSigned($historiesActions): Primitive
    {
        [$history, $lastHistoryOfStepNode] = $this->getCurrentLastHistoryAndLastHistoryOfStep(
            $historiesActions,
            MurabhaStep::ContractSigned
        );

        $data = [
            'step' => MurabhaStep::ContractSigned,
            'is_complete' => (bool) $history,
            'completed_at' => $history ? saudi_now('Y-m-d h:i:s A', $history->created_at) : null,
            'is_deliverable' => $this->traderOrder->isDeliverable(),
            'contract_signed_message' => Trader::driver($this->traderOrder->provider, $this->traderOrder->version)->contractSignedMessage($this->traderOrder),
            'wakala_document' => null,
            'duration' => $this->stepDurationService->getStepDuration($this->traderOrder, MurabhaStep::ContractSigned),
        ];

        if ($this->traderOrder->isVersion('v2')) {
            unset($data['is_deliverable']);
        }

        return $this->primitive($data);
    }
}
