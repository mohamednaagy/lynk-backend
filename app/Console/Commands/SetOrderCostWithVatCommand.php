<?php

namespace App\Console\Commands;

use App\Actions\Contracts\ProjectSettings\GetProjectSettings;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

class SetOrderCostWithVatCommand extends Command
{
    protected $signature = 'tiered-pricing:set-order-cost-with-vat {--company=244 : Company ID to process}';

    protected $description = 'Set order_cost_with_vat for fixed fee types in tiered_pricing table.';

    public function handle(GetProjectSettings $getProjectSettings): int
    {
        $vatMultiplier = 1 + $getProjectSettings->handle()->getVatRate();
        $companyId = (int) $this->option('company');

        $this->info("Starting VAT update for company ID {$companyId}");
        $this->info("VAT Multiplier: {$vatMultiplier}");

        $updatedIds = [];
        $failedIds = [];

        DB::table('tiered_pricing')
            ->where('company_id', $companyId)
            ->orderBy('id')
            ->chunkById(500, function ($records) use ($vatMultiplier, &$updatedIds, &$failedIds) {
                foreach ($records as $record) {
                    try {
                        $newValue = round($record->order_cost_without_vat * $vatMultiplier);

                        $affected = DB::table('tiered_pricing')
                            ->where('id', $record->id)
                            ->update(['order_cost_with_vat' => $newValue]);

                        if ($affected) {
                            $updatedIds[] = $record->id;
                        } else {
                            $failedIds[] = $record->id;
                        }
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
        $allRecordIds = DB::table('tiered_pricing')
            ->where('company_id', $companyId)
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
