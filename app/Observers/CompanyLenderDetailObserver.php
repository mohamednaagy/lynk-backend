<?php

namespace App\Observers;

use App\Enums\TraderOrderMode;
use App\Models\CompanyLenderDetail;

class CompanyLenderDetailObserver
{
    public function created(CompanyLenderDetail $lenderDetail): void
    {
        CompanyLenderDetail::flushCache();
    }

    public function updating(CompanyLenderDetail $lenderDetail): void
    {
        if ($lenderDetail->trading_mode->is(TraderOrderMode::Manual)) {
            $lenderDetail->preferred_market_type = null;
        }
    }

    public function updated(CompanyLenderDetail $lenderDetail): void
    {
        CompanyLenderDetail::flushCache();
    }

    public function deleted(CompanyLenderDetail $lenderDetail): void
    {
        CompanyLenderDetail::flushCache();
    }
}
