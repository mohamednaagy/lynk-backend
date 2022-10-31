<?php

namespace App\Actions\Contracts\Orders;

use App\Actions\Contracts\Wallets\CreateTransactions;
use App\Models\FinancingOrder;

interface CreateFinancingOrder
{
    /**
     * @param  CreateTransactions  $createTransactions
     */
    public function __construct(
        CreateTransactions $createTransactions,
    );

    public function handle(array $data): FinancingOrder;
}
