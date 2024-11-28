<?php

namespace App\Console\Commands;

use App\Services\LocalMarket\LiveMarketService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class BuildLiveMarketFromScratch extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'live-market:build';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Build live market from scratch';

    protected LiveMarketService $liveMarketService;

    private Carbon $startTime;

    /**
     * Create a new command instance.
     */
    public function __construct(LiveMarketService $liveMarketService)
    {
        parent::__construct();
        $this->liveMarketService = $liveMarketService;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->startTime = now();
        $this->info('Starting live market build at: '.$this->startTime->format('Y-m-d H:i:s'));

        try {
            $result = $this->liveMarketService->buildFromScratch(
                function (array $progress) {
                    $this->outputProgress($progress);
                }
            );

            $endTime = now();

            $this->outputResults($result);
            $this->outputTimingInfo($endTime);

            $this->info('Live market build completed successfully.');

            return self::SUCCESS;

        } catch (\Exception $e) {
            $endTime = now();
            $this->outputTimingInfo($endTime);

            $this->error('Failed to build live market: '.$e->getMessage());
            $this->error('Check live-market.log for details');

            return self::FAILURE;
        }
    }

    /**
     * Output progress information
     *
     * @param  array  $progress  Progress information
     */
    private function outputProgress(array $progress): void
    {
        $this->info(sprintf(
            'Processing: Inventory %d with Company %d (%d/%d)',
            $progress['inventory']->id,
            $progress['company']->id,
            $progress['current'],
            $progress['total']
        ));
    }

    /**
     * Output build results
     *
     * @param  array  $result  Build results
     */
    private function outputResults(array $result): void
    {
        $this->info('Build Statistics:');
        $this->table(
            ['Metric', 'Value'],
            [
                ['Companies Processed', $result['companies_processed']],
                ['Inventories Processed', $result['inventories_processed']],
                ['Total Operations', $result['total_operations']],
                ['Records Created', $result['records_created']],
            ]
        );
    }

    /**
     * Output timing information
     */
    private function outputTimingInfo(Carbon $endTime): void
    {
        $duration = $endTime->diffInSeconds($this->startTime);

        $this->table(
            ['Timing Metric', 'Value'],
            [
                ['Start Time', $this->startTime->format('Y-m-d H:i:s')],
                ['End Time', $endTime->format('Y-m-d H:i:s')],
                ['Duration', $this->formatDuration($duration)],
            ]
        );
    }

    /**
     * Format duration in human-readable format
     */
    private function formatDuration(int $seconds): string
    {
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        $remainingSeconds = $seconds % 60;

        $parts = [];

        if ($hours > 0) {
            $parts[] = "$hours hour".($hours > 1 ? 's' : '');
        }

        if ($minutes > 0) {
            $parts[] = "$minutes minute".($minutes > 1 ? 's' : '');
        }

        if ($remainingSeconds > 0 || empty($parts)) {
            $parts[] = "$remainingSeconds second".($remainingSeconds !== 1 ? 's' : '');
        }

        return implode(', ', $parts);
    }
}
