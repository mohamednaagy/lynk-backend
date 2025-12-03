<?php

namespace App\Console\Commands;

use App\Enums\CompanyType;
use App\Jobs\Reports\SupplierMonthlyUsageJob;
use App\Models\Company;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class GenerateSupplierMonthlyUsageReportsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reports:generate-supplier-monthly-usage
                            {--start-date= : Optional period start datetime (Y-m-d H:i:s)}
                            {--end-date= : Optional period end datetime (Y-m-d H:i:s)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate monthly usage reports for all suppliers';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $startInput = $this->option('start-date');
        $endInput = $this->option('end-date');

        // If one of the dates is provided, both must be.
        if (($startInput && ! $endInput) || ($endInput && ! $startInput)) {
            $this->error('Both --start-date and --end-date must be provided together.');

            return Command::FAILURE;
        }

        if ($startInput && $endInput) {
            try {
                $start = Carbon::parse($startInput);
                $end = Carbon::parse($endInput);
            } catch (\Throwable $e) {
                $this->error('Invalid start or end date provided. Please use a valid datetime string (Y-m-d H:i:s).');

                return Command::FAILURE;
            }

            if ($end->lt($start)) {
                $this->error('The end date must be greater than or equal to the start date.');

                return Command::FAILURE;
            }

            $startDate = $start->format('Y-m-d H:i:s');
            $endDate = $end->format('Y-m-d H:i:s');
        } else {
            // No explicit dates provided, fall back to previous full month.
            [$startDate, $endDate] = $this->getReportingPeriod();
        }

        Log::info('GenerateSupplierMonthlyUsageReportsCommand started', [
            'period_start' => $startDate,
            'period_end' => $endDate,
        ]);

        Log::info("Period: {$startDate} to {$endDate}");

        // Load all suppliers once and rely on collection helpers to check emptiness / count.
        $suppliers = Company::where('type', CompanyType::Supplier)->get();

        if ($suppliers->isEmpty()) {
            Log::warning('No suppliers found.');
            $this->warn('No suppliers found.');

            return Command::SUCCESS;
        }

        Log::info("Processing {$suppliers->count()} supplier(s)...");

        $successCount = 0;
        $failureCount = 0;
        $failedSuppliers = [];

        foreach ($suppliers as $supplier) {
            try {
                SupplierMonthlyUsageJob::dispatch(
                    $supplier->id,
                    $startDate,
                    $endDate
                );

                $successCount++;
            } catch (\Exception $e) {
                Log::error("Failed to dispatch SupplierMonthlyUsageJob for supplier id {$supplier->id}", [
                    'supplier_id' => $supplier->id,
                    'period_start' => $startDate,
                    'period_end' => $endDate,
                    'exception' => $e->getMessage(),
                ]);
                $failedSuppliers[] = [
                    'id' => $supplier->id,
                    'name' => $supplier->name ?? 'N/A',
                ];
                $failureCount++;
            }
        }

        Log::info("Summary: {$successCount} dispatched, {$failureCount} failed", [
            'success_count' => $successCount,
            'failure_count' => $failureCount,
        ]);

        if ($failureCount > 0) {
            Log::error('Failed suppliers', [
                'failed_suppliers' => $failedSuppliers,
            ]);
            $this->error('Failed suppliers:');
            foreach ($failedSuppliers as $failed) {
                $this->error("  - ID: {$failed['id']} ({$failed['name']})");
            }

            return Command::FAILURE;
        }

        Log::info('All jobs dispatched successfully.', [
            'total_dispatched' => $successCount,
        ]);

        return Command::SUCCESS;
    }

    /**
     * Get the default reporting period as start and end datetime strings.
     *
     * This returns the previous full calendar month based on the
     * current time when explicit dates are not provided.
     *
     * @return array{0:string,1:string}
     */
    private function getReportingPeriod(): array
    {
        $now = Carbon::now();
        $previousMonth = $now->copy()->subMonth();

        $startDate = $previousMonth->copy()->startOfMonth()->format('Y-m-d H:i:s');
        $endDate = $previousMonth->copy()->endOfMonth()->format('Y-m-d H:i:s');

        return [$startDate, $endDate];
    }
}
