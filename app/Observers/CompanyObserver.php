<?php

namespace App\Observers;

use App\Jobs\LocalMarket\InventoryEligibleQuantities\DeleteCompany;
use App\Jobs\LocalMarket\InventoryEligibleQuantities\RebuildLender;
use App\Models\Company;

class CompanyObserver
{
    /**
     * Handle the Company "created" event.
     */
    public function created(Company $company): void
    {
        RebuildLender::dispatch($company->id);
    }

    /**
     * Handle the Company "updated" event.
     */
    public function updated(Company $company): void {}

    /**
     * Handle the Company "deleted" event.
     */
    public function deleted(Company $company): void
    {
        DeleteCompany::dispatch($company->id);
    }
}
