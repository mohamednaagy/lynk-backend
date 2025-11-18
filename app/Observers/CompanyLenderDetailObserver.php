<?php

namespace App\Observers;

use App\Enums\TraderOrderMode;
use App\Models\CompanyLenderDetail;

class CompanyLenderDetailObserver
{
    public function updating(CompanyLenderDetail $lenderDetail): void
    {
        if ($lenderDetail->trading_mode->is(TraderOrderMode::Manual)) {
            $lenderDetail->preferred_market_type = null;
        }
    }
}
