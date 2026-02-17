<?php

declare(strict_types=1);

namespace App\Services\TraderOrder;

use App\Exceptions\InvalidStepDurationConfigException;
use App\Models\TraderOrder;
use App\Models\TraderOrderDuration;
use Carbon\Carbon;
use Carbon\CarbonInterval;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Config;

class StepDurationService
{
    public function setStepDuration(TraderOrder $traderOrder, int $traderHistoryAction): void
    {
        $stepDuration = $this->getStepDefinitionForEndHistory(
            $traderOrder->provider,
            $traderOrder->version,
            $traderOrder->contract_signed_type?->value,
            $traderHistoryAction
        );

        if ($stepDuration === null) {
            return;
        }

        $this->updateDurationWhenStepCompleted($traderOrder, $stepDuration);

    }

    public function updateDurationWhenStepCompleted(TraderOrder $traderOrder, array $stepDuration): void
    {
        try {
            $histories = $traderOrder->traderHistories;

            $startTime = $this->getCreatedAtForAction($histories, $stepDuration['start_history']);
            $endTime = $this->getCreatedAtForAction($histories, $stepDuration['end_history']);
            if ($startTime === null || $endTime === null) {
                return;
            }

            $durationSeconds = $this->durationInSeconds($startTime, $endTime);

            $this->persistDuration($traderOrder->id, $stepDuration['step'], $durationSeconds);
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * @return array{step: string, start_history: int, end_history: int}|null
     */
    public function getStepDefinitionForEndHistory(
        string $provider,
        string $version,
        ?int $contractSignedType,
        int $endHistoryAction
    ): ?array {
        $stepsConfig = Config::get("murabha-steps.{$provider}-step-duration.{$version}.{$contractSignedType}");

        if (! is_array($stepsConfig) || ! array_key_exists($endHistoryAction, $stepsConfig)) {
            return null;
        }

        $step = $stepsConfig[$endHistoryAction];

        if (empty($step['step']) || empty($step['start_history']) || empty($step['end_history'])) {
            throw InvalidStepDurationConfigException::missingRequiredKeys(
                $provider,
                $version,
                $contractSignedType,
                $endHistoryAction
            );
        }

        return $step;
    }

    private function getCreatedAtForAction(Collection $histories, int $action): ?Carbon
    {
        $record = $histories->firstWhere('action', $action);

        return $record !== null ? Carbon::make($record->created_at) : null;
    }

    private function durationInSeconds(Carbon $start, Carbon $end): int
    {
        $seconds = (int) $start->diffInSeconds($end);

        return $seconds;
    }

    private function persistDuration(int $traderOrderId, string $stepColumn, int $durationSeconds): void
    {
        TraderOrderDuration::updateOrCreate(['trader_order_id' => $traderOrderId], [$stepColumn => $durationSeconds]);
    }

    public function getStepDuration(TraderOrder $traderOrder, string $step): ?string
    {
        $seconds = $traderOrder->traderOrderDuration?->{$step};
        if ($seconds === null) {
            return null;
        }

        return CarbonInterval::seconds($seconds)->cascade()->forHumans();
    }
}
