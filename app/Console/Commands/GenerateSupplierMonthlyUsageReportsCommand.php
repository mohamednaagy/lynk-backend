<?php

namespace App\Console\Commands;

use App\Enums\CompanyType;
use App\Jobs\Reports\SupplierMonthlyUsageJob;
use App\Models\Company;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Psr\Log\LoggerInterface;

class GenerateSupplierMonthlyUsageReportsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reports:generate-supplier-monthly-usage';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate monthly usage reports for all suppliers';

    protected function log(): LoggerInterface
    {
        return Log::channel(LOG_CHANNEL_REPORTS);
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        // Get date range from .env (REPORTS_START_DATE, REPORTS_END_DATE) or fallback to previous month
        // Format: Y-m-d H:i:s (e.g., 2025-08-01 00:00:00)
        $startDate = config('services.reports.supplier_monthly_usage.start_date');
        $endDate = config('services.reports.supplier_monthly_usage.end_date');

        // If not set in .env, use previous month as default
        if (! $startDate || ! $endDate) {
            $now = Carbon::now();
            $previousMonth = $now->copy()->subMonth();

            $startDate = $previousMonth->copy()->startOfMonth()->format('Y-m-d H:i:s');
            $endDate = $previousMonth->copy()->endOfMonth()->format('Y-m-d H:i:s');
        }

        $this->log()->info('GenerateSupplierMonthlyUsageReportsCommand started', [
            'period_start' => $startDate,
            'period_end' => $endDate,
        ]);

        $this->log()->info("Period: {$startDate} to {$endDate}");

        $totalSuppliers = Company::where('type', CompanyType::Supplier)->count();

        if ($totalSuppliers === 0) {
            $this->log()->warning('No suppliers found.');
            $this->warn('No suppliers found.');

            return Command::SUCCESS;
        }

        $this->log()->info("Processing {$totalSuppliers} supplier(s)...");

        $successCount = 0;
        $failureCount = 0;
        $failedSuppliers = [];

        Company::where('type', CompanyType::Supplier)
            ->chunk(100, function ($suppliers) use ($startDate, $endDate, &$successCount, &$failureCount, &$failedSuppliers) {
                foreach ($suppliers as $supplier) {
                    try {
                        SupplierMonthlyUsageJob::dispatch(
                            $supplier->id,
                            $startDate,
                            $endDate
                        );

                        $successCount++;
                    } catch (\Exception $e) {
                        $this->error("Failed to dispatch job for supplier ID: {$supplier->id} ({$e->getMessage()})");
                        $failedSuppliers[] = [
                            'id' => $supplier->id,
                            'name' => $supplier->name ?? 'N/A',
                        ];
                        $failureCount++;
                    }
                }
            });

        $this->log()->info("Summary: {$successCount} dispatched, {$failureCount} failed", [
            'success_count' => $successCount,
            'failure_count' => $failureCount,
        ]);

        if ($failureCount > 0) {
            $this->log()->error('Failed suppliers', [
                'failed_suppliers' => $failedSuppliers,
            ]);
            $this->error('Failed suppliers:');
            foreach ($failedSuppliers as $failed) {
                $this->error("  - ID: {$failed['id']} ({$failed['name']})");
            }

            return Command::FAILURE;
        }

        $this->log()->info('All jobs dispatched successfully.', [
            'total_dispatched' => $successCount,
        ]);

        return Command::SUCCESS;
    }
}
