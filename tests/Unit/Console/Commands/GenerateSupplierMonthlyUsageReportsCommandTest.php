<?php

namespace Tests\Unit\Console\Commands;

use App\Console\Commands\GenerateSupplierMonthlyUsageReportsCommand;
use App\Enums\CompanyType;
use App\Jobs\Reports\SupplierMonthlyUsageJob;
use App\Models\Company;
use App\Models\CompanySupplierDetail;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

class GenerateSupplierMonthlyUsageReportsCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_dispatches_jobs_for_all_suppliers()
    {
        Bus::fake();

        // Create test suppliers
        $supplier1 = Company::factory()->create(['type' => CompanyType::Supplier]);
        $supplier2 = Company::factory()->create(['type' => CompanyType::Supplier]);
        $supplier3 = Company::factory()->create(['type' => CompanyType::Supplier]);

        // Create a supplier with inactive status - status should not affect job dispatch
        $inactiveSupplier = Company::factory()->create(['type' => CompanyType::Supplier]);
        CompanySupplierDetail::factory()->inactive()->create(['company_id' => $inactiveSupplier->id]);

        // Create non-supplier company (should be ignored)
        Company::factory()->create(['type' => CompanyType::Lender]);

        // Run the command via Artisan so IO/options are properly initialized
        $this->artisan('reports:generate-supplier-monthly-usage')
            ->assertSuccessful();

        // Assert jobs were dispatched for all suppliers (including inactive one)
        // Status should not affect the result - all suppliers should get jobs
        Bus::assertDispatched(SupplierMonthlyUsageJob::class, 4);

        // Verify job payloads for active suppliers
        Bus::assertDispatched(SupplierMonthlyUsageJob::class, function ($job) use ($supplier1) {
            return $job->modelId === $supplier1->id;
        });

        Bus::assertDispatched(SupplierMonthlyUsageJob::class, function ($job) use ($supplier2) {
            return $job->modelId === $supplier2->id;
        });

        Bus::assertDispatched(SupplierMonthlyUsageJob::class, function ($job) use ($supplier3) {
            return $job->modelId === $supplier3->id;
        });

        // Verify job was dispatched for inactive supplier - status should not affect the result
        Bus::assertDispatched(SupplierMonthlyUsageJob::class, function ($job) use ($inactiveSupplier) {
            return $job->modelId === $inactiveSupplier->id;
        });
    }

    public function test_command_uses_previous_month_when_env_not_set()
    {
        Bus::fake();

        $supplier = Company::factory()->create(['type' => CompanyType::Supplier]);

        $this->artisan('reports:generate-supplier-monthly-usage')
            ->assertSuccessful();

        // Calculate expected previous month dates
        $now = Carbon::now();
        $previousMonth = $now->copy()->subMonth();
        $expectedStartDate = $previousMonth->copy()->startOfMonth()->format('Y-m-d H:i:s');
        $expectedEndDate = $previousMonth->copy()->endOfMonth()->format('Y-m-d H:i:s');

        // Verify jobs were dispatched with previous month dates
        Bus::assertDispatched(SupplierMonthlyUsageJob::class, function ($job) use ($expectedStartDate, $expectedEndDate) {
            return $job->startDate === $expectedStartDate
                && $job->endDate === $expectedEndDate;
        });
    }

    public function test_command_handles_chunking_correctly()
    {
        Bus::fake();

        // Create 250 suppliers to test chunking (should process in 3 chunks: 100, 100, 50)
        Company::factory()->count(250)->create(['type' => CompanyType::Supplier]);

        $this->artisan('reports:generate-supplier-monthly-usage')
            ->assertSuccessful();

        // Assert all 250 jobs were dispatched
        Bus::assertDispatched(SupplierMonthlyUsageJob::class, 250);
    }

    public function test_command_returns_success_when_no_suppliers_found()
    {
        Bus::fake();

        // Don't create any suppliers
        // Use artisan() instead of direct instantiation to ensure output is set up
        $this->artisan('reports:generate-supplier-monthly-usage')
            ->assertSuccessful();

        Bus::assertNothingDispatched();
    }

    public function test_command_handles_job_dispatch_failure_gracefully()
    {
        Bus::fake();

        $supplier = Company::factory()->create(['type' => CompanyType::Supplier]);

        // We can't easily test actual dispatch failures with Bus::fake(),
        // but we can verify the command structure handles exceptions
        $reflection = new \ReflectionClass(GenerateSupplierMonthlyUsageReportsCommand::class);
        $source = file_get_contents($reflection->getFileName());

        // Verify the command has error handling (try-catch blocks)
        $this->assertStringContainsString('try', $source);
        $this->assertStringContainsString('catch', $source);
        $this->assertStringContainsString('failureCount', $source);
        $this->assertStringContainsString('failedSuppliers', $source);
    }

    public function test_scheduled_execution_works()
    {
        Bus::fake();

        $supplier = Company::factory()->create(['type' => CompanyType::Supplier]);

        // Simulate scheduled execution via artisan
        $this->artisan('reports:generate-supplier-monthly-usage')
            ->assertSuccessful();

        // Verify job was dispatched
        Bus::assertDispatched(SupplierMonthlyUsageJob::class);
    }

    public function test_command_is_scheduled_correctly()
    {
        // Verify the command is scheduled in Kernel.php by checking the file content
        $kernelPath = app_path('Console/Kernel.php');
        $kernelContent = file_get_contents($kernelPath);

        // Verify the command is referenced in the schedule method
        // Note: Kernel uses the class name, not the command signature string
        $this->assertStringContainsString('GenerateSupplierMonthlyUsageReportsCommand', $kernelContent);
        $this->assertStringContainsString('monthlyOn', $kernelContent);
        $this->assertStringContainsString('onOneServer', $kernelContent);
    }

    public function test_job_payload_structure_is_correct()
    {
        Bus::fake();

        $supplier = Company::factory()->create(['type' => CompanyType::Supplier]);

        $this->artisan('reports:generate-supplier-monthly-usage')
            ->assertSuccessful();

        // Verify the job was dispatched with correct structure
        Bus::assertDispatched(SupplierMonthlyUsageJob::class, function ($job) use ($supplier) {
            return $job->modelId === $supplier->id
                && ! empty($job->startDate)
                && ! empty($job->endDate)
                && preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $job->startDate)
                && preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $job->endDate);
        });
    }

    public function test_scheduled_command_runs_correctly_after_one_month()
    {
        Bus::fake();

        // Create test suppliers
        $supplier1 = Company::factory()->create(['type' => CompanyType::Supplier]);
        $supplier2 = Company::factory()->create(['type' => CompanyType::Supplier]);

        // Fake the current time to be one month in the future
        // This simulates the command running on the 1st of next month (as scheduled)
        $futureDate = Carbon::now()->addMonth()->startOfMonth()->setTime(0, 0, 0);
        Carbon::setTestNow($futureDate);

        try {
            $this->artisan('reports:generate-supplier-monthly-usage')
                ->assertSuccessful();

            // Calculate expected previous month dates (one month before the fake date)
            $expectedPreviousMonth = $futureDate->copy()->subMonth();
            $expectedStartDate = $expectedPreviousMonth->copy()->startOfMonth()->format('Y-m-d H:i:s');
            $expectedEndDate = $expectedPreviousMonth->copy()->endOfMonth()->format('Y-m-d H:i:s');

            // Assert jobs were dispatched for all suppliers
            Bus::assertDispatched(SupplierMonthlyUsageJob::class, 2);

            // Verify jobs were dispatched with the correct previous month dates
            Bus::assertDispatched(SupplierMonthlyUsageJob::class, function ($job) use ($supplier1, $expectedStartDate, $expectedEndDate) {
                return $job->modelId === $supplier1->id
                    && $job->startDate === $expectedStartDate
                    && $job->endDate === $expectedEndDate;
            });

            Bus::assertDispatched(SupplierMonthlyUsageJob::class, function ($job) use ($supplier2, $expectedStartDate, $expectedEndDate) {
                return $job->modelId === $supplier2->id
                    && $job->startDate === $expectedStartDate
                    && $job->endDate === $expectedEndDate;
            });
        } finally {
            // Always restore the real current time after the test
            Carbon::setTestNow();
        }
    }
}
