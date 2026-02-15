<?php

namespace App\Observers;

use App\Models\Lender;

class LenderObserver
{
    /**
     * Handle the Company "created" event.
     */
    public function created(Lender $lender): void
    {
        Lender::flushCache();
    }

    /**
     * Handle the Company "updated" event.
     */
    public function updated(Lender $lender): void
    {
        Lender::flushCache();
    }

    /**
     * Handle the Company "deleted" event.
     */
    public function deleted(Lender $lender): void
    {
        Lender::flushCache();
    }
}
