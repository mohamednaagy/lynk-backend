<?php

namespace App\Actions\Contracts\Wallets;

interface GetTransactions
{
    /**
     * @return mixed
     */
    public function handle(): mixed;
}
