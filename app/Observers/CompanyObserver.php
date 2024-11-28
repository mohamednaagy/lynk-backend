<?php

namespace App\Observers;

use App\Enums\CompanyStatus;
use App\Models\Company;
use App\Services\LocalMarket\LiveMarketService;

class CompanyObserver
{
    protected LiveMarketService $liveMarketService;

    public function __construct(LiveMarketService $liveMarketService)
    {
        $this->liveMarketService = $liveMarketService;
    }

    /**
     * Handle the Company "created" event.
     */
    public function created(Company $company): void
    {
        $this->liveMarketService->handleNewCompany($company);
    }

    /**
     * Handle the Company "updated" event.
     */
    public function updated(Company $company): void
    {
        // If status changed to Approved, handle as new company
        if ($company->wasChanged('status') && $company->status === CompanyStatus::Approved) {
            $this->liveMarketService->handleNewCompany($company);
        }

        // If status changed from Approved to something else, remove from live market
        if (
            $company->wasChanged('status')
            && $company->getOriginal('status') === CompanyStatus::Approved
            && $company->status !== CompanyStatus::Approved
        ) {
            $this->liveMarketService->handleCompanyRemoval($company);
        }
    }

    /**
     * Handle the Company "deleted" event.
     */
    public function deleted(Company $company): void
    {
        $this->liveMarketService->handleCompanyRemoval($company);
    }
}
