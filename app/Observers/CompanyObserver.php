<?php

namespace App\Observers;

use App\Enums\CompanyStatus;
use App\Enums\CompanyType;
use App\Jobs\LocalMarket\InventoryEligibleQuantities\DeleteCompany;
use App\Jobs\LocalMarket\InventoryEligibleQuantities\RebuildLender;
use App\Jobs\LocalMarket\LiveMarket\DeleteSupplierFromLiveMarket;
use App\Jobs\LocalMarket\LiveMarket\PublishLenderToLiveMarket;
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
        RebuildLender::dispatch($company->id);

        // if ($company->type->is(CompanyType::Lender) && $company->status->is(CompanyStatus::Approved)) {
        //     PublishLenderToLiveMarket::dispatch($company);
        // }
    }

    /**
     * Handle the Company "updated" event.
     */
    public function updated(Company $company): void
    {

        // // Handle lender status changes
        // if ($company->type->is(CompanyType::Lender) && $company->wasChanged('status')) {
        //     // If status changed to Approved, handle as new company
        //     if ($company->status->is(CompanyStatus::Approved)) {
        //         PublishLenderToLiveMarket::dispatch($company);
        //     } else {
        //         DeleteSupplierFromLiveMarket::dispatch($company);
        //     }
        // }
    }

    /**
     * Handle the Company "deleted" event.
     */
    public function deleted(Company $company): void
    {
        DeleteCompany::dispatch($company->id);
        // if ($company->type->is(CompanyType::Lender)) {
        //     DeleteSupplierFromLiveMarket::dispatch($company);
        // }
    }
}
