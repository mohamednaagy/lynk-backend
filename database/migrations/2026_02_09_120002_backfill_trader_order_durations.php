<?php

declare(strict_types=1);

use App\Models\TraderOrder;
use App\Services\TraderOrder\TraderOrderDurationService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Config;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $service = app(TraderOrderDurationService::class);

        TraderOrder::query()
            ->chunk(100, function ($traderOrders) use ($service): void {
                foreach ($traderOrders as $traderOrder) {
                    $traderOrder->load('traderHistories');
                    $provider = (string) $traderOrder->provider;
                    $version = (string) $traderOrder->version;
                    $contractType = $traderOrder->contract_signed_type?->value;
                    $configKey = TraderOrderDurationService::STEP_CONFIG_KEY_MAP[$provider];

                    $stepsConfig = Config::get("murabha-steps.{$configKey}.{$version}.{$contractType}");

                    foreach ($stepsConfig as $endHistoryAction => $stepDef) {
                        if (! is_array($stepDef) || empty($stepDef['end_history'])) {
                            continue;
                        }
                        if (! $traderOrder->traderHistories->contains('action', $endHistoryAction)) {
                            continue;
                        }
                        $stepDefinition = $service->getStepDefinitionForEndHistory($provider, $version, $contractType, $endHistoryAction);
                        if ($stepDefinition) {
                            $service->updateDurationWhenStepCompleted($traderOrder, $stepDefinition);
                        }
                    }
                }
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        TraderOrder::query()
            ->chunk(100, function ($traderOrders): void {
                foreach ($traderOrders as $traderOrder) {
                    $traderOrder->traderOrderDuration?->delete();
                }
            });
    }
};
