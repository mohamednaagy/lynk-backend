<?php

declare(strict_types=1);

use App\Models\TraderOrder;
use App\Services\TraderOrder\StepDurationService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Config;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $service = app(StepDurationService::class);

        TraderOrder::query()
            ->chunk(100, function ($traderOrders) use ($service): void {
                foreach ($traderOrders as $traderOrder) {
                    $traderOrder->load('traderHistories');
                    $provider = (string) $traderOrder->provider;
                    $version = (string) $traderOrder->version;
                    $contractType = $traderOrder->contract_signed_type?->value;

                    $stepsConfig = Config::get("murabha-steps.{$provider}-step-duration.{$version}.{$contractType}");

                    foreach ($stepsConfig as $endHistoryAction => $stepDef) {
                        if (! is_array($stepDef) || empty($stepDef['end_history'])) {
                            continue;
                        }
                        if (! $traderOrder->traderHistories->contains('action', $endHistoryAction)) {
                            continue;
                        }
                        $service->setStepDuration($traderOrder, $endHistoryAction);
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
