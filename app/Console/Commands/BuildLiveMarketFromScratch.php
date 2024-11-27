<?php

namespace App\Console\Commands;

use App\Enums\CompanyStatus;
use App\Enums\CompanyType;
use App\Enums\LocalMarket\InventoryStatus;
use App\Models\Company;
use App\Models\LocalMarketInventory;
use App\Services\LocalMarket\LiveMarketService;
use Illuminate\Console\Command;

class BuildLiveMarketFromScratch extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'live-market:build {--no-progress : Hide the progress bar}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Build the live market from scratch';

    /**
     * Execute the console command.
     */
    public function handle(LiveMarketService $liveMarketService): int
    {
        $this->info('Starting to build live market...');
        $startTime = now();

        try {
            // Get initial counts for progress bar and statistics
            $allLendersCount = Company::where('type', CompanyType::Lender)->count();
            $activeLendersCount = Company::where('status', CompanyStatus::Approved)
                ->where('type', CompanyType::Lender)
                ->count();

            $inventoriesCount = LocalMarketInventory::where('status', InventoryStatus::Active)
                ->where('available_quantity', '>', 0)
                ->count();

            $totalOperations = $activeLendersCount * $inventoriesCount;

            // Skip progress bar if --no-progress option is used
            if ($this->option('no-progress')) {
                $stats = $liveMarketService->buildFromScratch();
            } else {
                // Create progress bar
                $progressBar = $this->output->createProgressBar($totalOperations);
                $progressBar->setFormat(
                    "%current%/%max% [%bar%] %percent:3s%%\n".
                    'Processing: %message%'
                );

                // Build with progress tracking
                $stats = $liveMarketService->buildFromScratch(function ($data) use ($progressBar) {
                    $inventory = $data['inventory'];
                    $company = $data['company'];

                    $progressBar->setMessage("Inventory #{$inventory->id} for Company #{$company->id}");
                    $progressBar->setProgress($data['current']);
                });

                $progressBar->finish();
                $this->newLine(2);
            }

            $endTime = now();
            $duration = $endTime->diffInSeconds($startTime);

            $this->info('Live market built successfully!');

            // Show summary if we're showing progress
            if (! $this->option('no-progress')) {
                $this->table(
                    ['Statistics', 'Count'],
                    [
                        ['Start Time', $startTime->format('Y-m-d H:i:s')],
                        ['End Time', $endTime->format('Y-m-d H:i:s')],
                        ['Duration', $this->formatDuration($duration)],
                        ['Total Lenders', $allLendersCount],
                        ['Active Lenders', $activeLendersCount],
                        ['Active Inventories', $stats['inventories_processed']],
                        ['Total Operations', $stats['total_operations']],
                        ['Records Created', $stats['records_created']],
                    ]
                );
            } else {
                $this->info('Build completed in: '.$this->formatDuration($duration));
                $this->info('Records created: '.$stats['records_created']);
                $this->info('Active lenders processed: '.$activeLendersCount);
            }

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $endTime = now();
            $duration = $endTime->diffInSeconds($startTime);

            $this->error('Failed to build live market: '.$e->getMessage());
            $this->error('Failed after: '.$this->formatDuration($duration));

            return Command::FAILURE;
        }
    }

    /**
     * Format duration in a human-readable format
     */
    private function formatDuration(int $seconds): string
    {
        if ($seconds < 60) {
            return "{$seconds} seconds";
        }

        if ($seconds < 3600) {
            $minutes = floor($seconds / 60);
            $remainingSeconds = $seconds % 60;

            return "{$minutes} minutes, {$remainingSeconds} seconds";
        }

        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        $remainingSeconds = $seconds % 60;

        return "{$hours} hours, {$minutes} minutes, {$remainingSeconds} seconds";
    }
}
