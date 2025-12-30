<?php

namespace App\Actions\Contracts\Wallets;

use App\Models\Lender;

interface GetTransactions
{
    public function handle(): mixed;

    public function attachZatcaInvoicesMedia(): self;

    public function setLender(Lender $lender): self;

    public function setFilters(array $filters): self;
}
