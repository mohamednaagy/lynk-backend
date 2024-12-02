<?php

namespace App\Observers;

use App\Models\Company;

class SupplierObserver
{
    public function __construct() {}

    /**
     * Handle the Company "created" event.
     */
    public function created(Company $company): void {}

    /**
     * Handle the Company "updated" event.
     */
    public function updated(Company $company): void {}

    /**
     * Handle the Company "deleted" event.
     */
    public function deleted(Company $company): void {}
}
