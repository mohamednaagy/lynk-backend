<?php

namespace App\Actions\Wallets;

use App\Actions\Contracts\Wallets\CreateWallet;
use App\Models\Company;
use App\Models\Wallet;

class CreateWalletAction implements CreateWallet
{
    public function handle(Company $company, string $walletType, string $currency): Wallet
    {
        return $company->createWallet($walletType, $currency);
    }
}
