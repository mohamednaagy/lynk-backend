<?php

declare(strict_types=1);

namespace App\Services\TraderOrder;

use App\Exceptions\InvalidStepDurationConfigException;
use App\Models\TraderOrder;
use App\Models\TraderOrderDuration;
use Carbon\Carbon;
use Illuminate\Support\Facades\Config;

class TraderOrderDurationService
{
    public const STEP_CONFIG_KEY_MAP = [
        'lynk' => 'lynk-step-duration',
        'bursam' => 'bursam-step-duration',
        'dmcc' => 'dmcc-step-duration',
        'fake' => 'fake-step-duration',
    ];

    public function updateDurationWhenStepCompleted(TraderOrder $traderOrder, array $stepDuration): void
    {
        $histories = $traderOrder->traderHistories;

        $startTime = $this->getCreatedAtForAction($histories, $stepDuration['start_history']);
        $endTime = $this->getCreatedAtForAction($histories, $stepDuration['end_history']);
        $durationSeconds = $this->durationInSeconds($startTime, $endTime);

        $this->persistDuration($traderOrder->id, $stepDuration['step'], $durationSeconds);
    }

    /**
     * @return array{step: string, start_history: int, end_history: int}|null
     */
    public function getStepDefinitionForEndHistory(
        string $provider,
        string $version,
        ?int $contractSignedType,
        int $endHistoryAction
    ): array|false {
        $configKey = self::STEP_CONFIG_KEY_MAP[$provider];
        $stepsConfig = Config::get("murabha-steps.{$configKey}.{$version}.{$contractSignedType}");
        if (array_key_exists($endHistoryAction, $stepsConfig)) {
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

        return false;
    }

    private function getCreatedAtForAction($histories, int $action): Carbon
    {
        $record = $histories->where('action', $action)->first();

        return Carbon::make($record->created_at);
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
}
