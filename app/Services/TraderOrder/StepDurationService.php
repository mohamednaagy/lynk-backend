<?php

declare(strict_types=1);

namespace App\Services\TraderOrder;

use App\Exceptions\InvalidStepDurationConfigException;
use App\Models\TraderHistory;
use App\Models\TraderOrder;
use App\Models\TraderOrderDuration;
use Carbon\Carbon;
use Carbon\CarbonInterval;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

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

        if (is_null($stepDuration)) {
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
            if (is_null($startTime) || is_null($endTime)) {
                return;
            }
            $durationSeconds = $this->durationInSeconds($startTime, $endTime);

            $this->persistDuration($traderOrder->id, $stepDuration['step'], $durationSeconds);
        } catch (\Exception $e) {
            Log::channel(LOG_CHANNEL_LYNK)->error(formatLogTitle('Error while updating duration when step completed', $traderOrder), [
                'traderOrderId' => $traderOrder->id,
                'stepDuration' => $stepDuration,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
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

    /**
     * @param  Collection<int, TraderHistory>  $histories
     */
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

    private function getDuration(int $traderOrderId): ?TraderOrderDuration
    {
        return TraderOrderDuration::query()
            ->where('trader_order_id', $traderOrderId)
            ->first();
    }

    public function getStepDuration(TraderOrder $traderOrder, string $step): ?string
    {
        $seconds = $this->getDuration($traderOrder->id)?->{$step};
        if (is_null($seconds)) {
            return null;
        }

        return CarbonInterval::seconds($seconds)->cascade()->forHumans();
    }
}
