<?php

namespace App\Actions\Contracts\Wallets;

use App\Models\FinancingOrder;

interface DeductOrderCreationFee
{
    public function handle(CreateTransactions $createTransactions, FinancingOrder $financingOrder);
}
