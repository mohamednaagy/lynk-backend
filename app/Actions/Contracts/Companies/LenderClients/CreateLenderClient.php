<?php

namespace App\Actions\Contracts\Companies\LenderClients;

use App\Models\Company;
use App\Models\CompanyLenderClient;

interface CreateLenderClient
{
    public function handle(Company $lender, array $data): CompanyLenderClient;
}
