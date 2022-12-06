<?php

namespace App\Actions\Contracts\Wallets;

use App\Models\FinancingOrder;
use App\Models\Transaction;

interface GenerateFatoura
{
    public function handel(FinancingOrder $financingOrder, Transaction $transaction, array $data);
}
