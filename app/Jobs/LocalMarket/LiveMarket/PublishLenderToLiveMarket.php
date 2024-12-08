<?php

namespace App\Jobs\LocalMarket\LiveMarket;

use App\Models\Company;
use App\Services\LocalMarket\LiveMarketService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class PublishLenderToLiveMarket implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected Company $lender;

    /**
     * Create a new job instance.
     */
    public function __construct(Company $lender)
    {
        $this->lender = $lender;
        $this->onQueue('local_market');
    }

    /**
     * Execute the job.
     */
    public function handle(LiveMarketService $liveMarketService): void
    {
        try {
            Log::channel('live_market')->info('Starting to publish lender to live market', [
                'lender_id' => $this->lender->id,
                'lender_name' => $this->lender->name,
            ]);

            $liveMarketService->handleNewCompany($this->lender);

            Log::channel('live_market')->info('Successfully published lender to live market', [
                'lender_id' => $this->lender->id,
                'lender_name' => $this->lender->name,
            ]);
        } catch (\Exception $e) {
            $this->handleError($e);
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        $this->handleError($exception);
    }

    /**
     * Handle errors during job execution.
     */
    private function handleError(\Throwable $exception): void
    {
        Log::channel('live_market')->error('Failed to publish lender to live market', [
            'lender_id' => $this->lender->id,
            'lender_name' => $this->lender->name,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }
}
