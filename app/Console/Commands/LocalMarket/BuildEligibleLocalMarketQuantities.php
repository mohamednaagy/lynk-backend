<?php

namespace App\Console\Commands\LocalMarket;

use App\Services\LocalMarket\EligibleQuantityService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class BuildEligibleLocalMarketQuantities extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'local-market:build-eligible-quantities';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Build eligible quantities for local market inventories';

    /**
     * Execute the console command.
     */
    public function handle(EligibleQuantityService $service): int
    {
        try {
            $this->info('Starting to build eligible quantities...');

            $progress = $this->output->createProgressBar();
            $progress->start();

            $result = $service->buildFromScratch(function ($data) use ($progress) {
                $progress->advance();

                if ($this->output->isVerbose()) {
                    $this->line(sprintf(
                        'Processing inventory %d for company %d (%d/%d)',
                        $data['inventory']->id,
                        $data['company']->id,
                        $data['current'],
                        $data['total']
                    ));
                }
            });

            $progress->finish();
            $this->newLine();

            $this->table(
                ['Metric', 'Value'],
                collect($result)->map(fn ($value, $key) => [
                    str_replace('_', ' ', ucfirst($key)),
                    $value,
                ])->toArray()
            );

            $this->info('Eligible quantities built successfully!');

            return Command::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('Failed to build eligible quantities: '.$e->getMessage());

            Log::channel('live_market')->error('Failed to build eligible quantities via command', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return Command::FAILURE;
        }
    }
}
