<?php

namespace App\Actions\Wallets;

use App\Actions\Contracts\Wallets\CreateWallet;
use App\Models\Lender;
use App\Models\Wallet;

class CreateWalletAction implements CreateWallet
{
    public function handle(Lender $lender, string $walletType, string $currency): Wallet
    {
        return $lender->createWallet($walletType, $currency);
    }
}
