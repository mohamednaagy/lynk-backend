<?php

namespace App\Actions\Contracts\Wallets;

use App\Models\Company;
use App\Models\Wallet;

interface CreateWallet
{
    public function handle(Company $company, string $walletType, string $currency): Wallet;
}
