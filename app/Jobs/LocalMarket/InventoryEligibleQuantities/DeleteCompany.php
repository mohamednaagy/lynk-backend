<?php

namespace App\Jobs\LocalMarket\InventoryEligibleQuantities;

use App\Models\Company;
use App\Services\LocalMarket\EligibleQuantityService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class DeleteCompany implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private readonly int $companyId
    ) {
        $this->onQueue('local_market_eligible_quantities');
    }

    public function handle(EligibleQuantityService $service): void
    {
        try {
            $company = Company::withTrashed()->findOrFail($this->companyId);

            Log::channel('live_market')->info('Starting delete eligible quantities for company', [
                'company_id' => $this->companyId,
            ]);

            $service->deleteForLender($company);

            Log::channel('live_market')->info('Completed delete eligible quantities for company', [
                'company_id' => $this->companyId,
            ]);
        } catch (\Throwable $e) {
            Log::channel('live_market')->error('Failed to delete eligible quantities for company', [
                'company_id' => $this->companyId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }
}
