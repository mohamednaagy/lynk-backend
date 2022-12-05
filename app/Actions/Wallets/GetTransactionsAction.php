<?php

namespace App\Actions\Wallets;

use App\Actions\Contracts\Wallets\GetTransactions;
use App\Enums\WalletType;

class GetTransactionsAction implements GetTransactions
{
    /**
     * @return mixed
     */
    public function handle(): mixed
    {
        return tenant()->transactions(WalletType::CompanyWallet)->paginate();
    }
}
