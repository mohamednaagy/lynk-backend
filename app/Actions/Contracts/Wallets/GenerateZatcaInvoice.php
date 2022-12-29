<?php

namespace App\Actions\Contracts\Wallets;

use App\Models\FinancingOrder;
use App\Models\Transaction;

interface GenerateZatcaInvoice
{
    public function handel(FinancingOrder $financingOrder, Transaction $creationFeeTransaction, Transaction $vatPercentageTransaction);
}
