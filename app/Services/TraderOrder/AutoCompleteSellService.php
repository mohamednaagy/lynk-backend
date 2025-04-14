<?php

namespace App\Services\TraderOrder;

use App\Enums\Trader as TraderEnum;
use App\Enums\TraderOrderMode;
use App\Jobs\TraderOrder\AutoCompleteSell\AutoCompleteSell;
use App\Models\TraderHistory;
use App\Models\TraderOrder;
use App\Services\Company\CompanyLenderClientService;

class AutoCompleteSellService
{
    private TraderOrder $traderOrder;

    /**
     * Checks if the trader order qualifies for auto-complete sell,
     * and dispatches the job if conditions are met.
     */
    public function handleAutoCompleteSell(TraderHistory $traderHistory): void
    {
        $this->traderOrder = $traderHistory->traderOrder;

        if (! $this->isAutomaticMode() || ! $this->isSupportedProvider()) {
            return;
        }

        $periodId = CompanyLenderClientService::findActiveAutoCompletePeriodId($this->traderOrder->order);

        if (! $periodId) {
            return;
        }

        AutoCompleteSell::dispatch($this->traderOrder->id, $periodId);
    }

    private function isAutomaticMode(): bool
    {
        return $this->traderOrder->mode === TraderOrderMode::Automatic;
    }

    private function isSupportedProvider(): bool
    {
        return in_array($this->traderOrder->provider, [TraderEnum::Bursam, TraderEnum::Lynk]);
    }
}
