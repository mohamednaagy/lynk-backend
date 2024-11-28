<?php

namespace App\Observers;

use App\Enums\CompanyStatus;
use App\Enums\CompanyType;
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
        if ($company->type == CompanyType::Lender && $company->status == CompanyStatus::Approved) {
            $this->liveMarketService->handleNewCompany($company);
        }
    }

    /**
     * Handle the Company "updated" event.
     */
    public function updated(Company $company): void
    {
        // Handle supplier status changes
        if ($company->type == CompanyType::Supplier && $company->wasChanged('status')) {
            $this->liveMarketService->handleSupplierStatusChange($company);

            return;
        }

        // Handle lender status changes
        if ($company->type == CompanyType::Lender) {
            // If status changed to Approved, handle as new company
            if ($company->wasChanged('status') && $company->status == CompanyStatus::Approved) {
                $this->liveMarketService->handleNewCompany($company);
            }

            // If status changed from Approved to something else, remove from live market
            if (
                $company->wasChanged('status')
                && $company->getOriginal('status') == CompanyStatus::Approved
                && $company->status !== CompanyStatus::Approved
            ) {
                $this->liveMarketService->handleCompanyRemoval($company);
            }
        }
    }

    /**
     * Handle the Company "deleted" event.
     */
    public function deleted(Company $company): void
    {
        if ($company->type == CompanyType::Lender) {
            $this->liveMarketService->handleCompanyRemoval($company);
        }
    }
}
