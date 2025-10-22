<?php

namespace App\Console\Commands;

use App\Actions\Contracts\ProjectSettings\GetProjectSettings;
use App\Models\TieredPricing;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class SetVatAmountCommand extends Command
{
    protected $signature = 'tiered-pricing:set-vat-amount';

    protected $description = 'Set vat_amount based on order_cost_without_vat * 0.15 in tiered_pricing table.';

    public function handle(GetProjectSettings $getProjectSettings): int
    {
        $vatRate = $getProjectSettings->handle()->getVatRate();
        $this->info("VAT Rate: {$vatRate}");

        $updatedIds = [];
        $failedIds = [];

        Log::info('Start SetVatAmountCommand');

        TieredPricing::query()
            ->whereHas('company')
            ->chunkById(500, function ($records) use ($vatRate, &$updatedIds, &$failedIds) {
                foreach ($records as $record) {
                    try {
                        DB::transaction(function () use ($record, $vatRate, &$updatedIds) {
                            $orderCostWithoutVat = $record->order_cost_without_vat;

                            // Calculate total with VAT
                            $totalWithVat = $orderCostWithoutVat->multiply(1 + $vatRate);

                            // Round the total to get a clean number
                            $totalAmount = $totalWithVat->getAmount();

                            // Check the last digit and adjust if needed
                            $lastDigit = $totalAmount % 10;

                            if ($lastDigit == 1) {
                                // If last digit is 1, subtract 1 from total
                                $totalAmount -= 1;
                            } elseif ($lastDigit == 9) {
                                // If last digit is 9, add 1 to total
                                $totalAmount += 1;
                            }

                            $roundedTotal = money($totalAmount, $totalWithVat->getCurrency());

                            // VAT amount is the difference between adjusted total and original amount
                            $vatAmount = $roundedTotal->subtract($orderCostWithoutVat);

                            // 🪵 Log before & after values to storage/logs/laravel.log
                            Log::info('Updating tiered_pricing vat_amount', [
                                'record_id' => $record->id,
                                'company_id' => $record->company_id,
                                'order_cost_without_vat' => $orderCostWithoutVat->getAmount(),
                                'total_with_vat_before_round' => $totalWithVat->getAmount(),
                                'total_with_vat_after_round' => $roundedTotal->getAmount(),
                                'calculated_vat_amount' => $vatAmount->getAmount(),
                                'verification_sum' => $orderCostWithoutVat->add($vatAmount)->getAmount(),
                            ]);

                            $record->update([
                                'vat_amount' => $vatAmount,
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
