<?php

namespace App\Observers;

use App\Models\Company;
use App\Services\LocalMarket\LiveMarketService;

class SupplierObserver
{
    protected LiveMarketService $liveMarketService;

    public function __construct(LiveMarketService $liveMarketService)
    {
        $this->liveMarketService = $liveMarketService;
    }

    /**
     * Handle the Company "created" event.
     */
    public function created(Company $company): void {}

    /**
     * Handle the Company "updated" event.
     */
    public function updated(Company $company): void
    {
        // Handle supplier status changes
        if ($company->wasChanged('status')) {
            $this->liveMarketService->handleSupplierStatusChange($company);

            return;
        }
    }

    /**
     * Handle the Company "deleted" event.
     */
    public function deleted(Company $company): void {}
}
