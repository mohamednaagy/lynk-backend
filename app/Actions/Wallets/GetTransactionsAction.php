<?php

namespace App\Actions\Wallets;

use App\Actions\Contracts\Wallets\GetTransactions;

class GetTransactionsAction implements GetTransactions
{
    public function handle(array $data)
    {
        return auth()->user()->company->transactions()->paginate();
    }
}
