<?php

namespace App\Actions\Contracts\Traders;

use App\Models\Company;

interface ShowTrader
{
    public function handle(Company $trader);
}
