<?php

namespace App\Actions\Contracts\Wallets;

interface GetTransactions
{
    public function handle(array $data);
}
