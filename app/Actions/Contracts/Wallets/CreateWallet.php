<?php

namespace App\Actions\Contracts\Wallets;

use App\Models\Lender;
use App\Models\Wallet;

interface CreateWallet
{
    public function handle(Lender $lender, string $walletType, string $currency): Wallet;
}
