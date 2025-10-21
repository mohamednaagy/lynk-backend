<?php

namespace App\Console\Commands;

use App\Actions\Contracts\ProjectSettings\GetProjectSettings;
use App\Models\TieredPricing;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class FixOrderCostWithoutVatCommand extends Command
{
    protected $signature = 'tiered-pricing:set-order-cost-with-vat';

    protected $description = 'Set order_cost_with_vat for fixed fee types in tiered_pricing table.';

    public function handle(GetProjectSettings $getProjectSettings): int
    {
        $vatRate = $getProjectSettings->handle()->getVatRate();
        $this->info("VAT Rate: {$vatRate}");

        $updatedIds = [];
        $failedIds = [];

        Log::info('Start FixOrderCostWithoutVatCommand');

        TieredPricing::query()
            ->whereHas('company')
            ->chunkById(500, function ($records) use ($vatRate, &$updatedIds, &$failedIds) {
                foreach ($records as $record) {
                    try {
                        DB::transaction(function () use ($record, $vatRate, &$updatedIds) {
                            $oldValue = $record->temp_order_cost_without_vat;
                            $newValue = $record->temp_order_cost_without_vat
                                ->multiply(1 + $vatRate)
                                ->multiply(1 - $vatRate);

                            // 🪵 Log before & after values to storage/logs/laravel.log
                            Log::info('Updating tiered_pricing record', [
                                'record_id' => $record->id,
                                'company_id' => $record->company_id,
                                'old_value' => $oldValue->getAmount(),
                                'new_value' => $newValue->getAmount(),
                            ]);

                            $record->update([
                                'order_cost_without_vat' => $newValue,
                            ]);

                            $updatedIds[] = $record->id;
                        });
                    } catch (Throwable $e) {
                        $failedIds[] = $record->id;
                        $this->error("❌ Failed to update record ID {$record->id}: {$e->getMessage()}");
                    }
                }
            });
        $totalProcessed = count($updatedIds) + count($failedIds);

        $this->info("\n--- Summary ---");
        $this->info('✅ Updated: '.count($updatedIds));
        $this->warn('⚠️ Failed: '.count($failedIds));
        $this->info("Total processed: {$totalProcessed}");

        // 🔍 Verify all records handled
        $allRecordIds = TieredPricing::query()
            ->whereHas('company')
            ->pluck('id')
            ->toArray();

        $unhandledIds = array_diff($allRecordIds, array_merge($updatedIds, $failedIds));

        if (count($unhandledIds) > 0) {
            $this->warn('⚠️ '.count($unhandledIds).' records were not handled.');
            $this->warn('IDs: '.implode(', ', array_slice($unhandledIds, 0, 10)).(count($unhandledIds) > 10 ? '...' : ''));

            return self::FAILURE;
        }

        $this->info('🎉 All records have been updated or handled successfully.');

        return self::SUCCESS;
    }
}
