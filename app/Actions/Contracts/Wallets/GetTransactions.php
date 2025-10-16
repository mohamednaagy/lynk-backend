<?php

namespace App\Actions\Contracts\Wallets;

use App\Models\Company;

interface GetTransactions
{
    public function handle(): mixed;

    public function attachZatcaInvoicesMedia(): self;

    public function setCompany(Company $company): self;

    public function setFilters(array $filters): self;
}
