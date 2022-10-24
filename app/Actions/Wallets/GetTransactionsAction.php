<?php

namespace App\Actions\Wallets;

use App\Actions\Contracts\Wallets\GetTransactions;

class GetTransactionsAction implements GetTransactions
{
    /**
     * @return mixed
     */
    public function handle(): mixed
    {
        return tenant()->transactions()->paginate();
    }
}
