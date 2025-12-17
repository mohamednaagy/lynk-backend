<?php

namespace App\Actions\Contracts\Companies\LenderClients;

use App\Models\CompanyLenderClient;
use App\Models\Lender;

interface CreateLenderClient
{
    public function handle(Lender $lender, array $data): CompanyLenderClient;
}
