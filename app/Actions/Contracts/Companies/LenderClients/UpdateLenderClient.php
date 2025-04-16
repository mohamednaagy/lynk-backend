<?php

namespace App\Actions\Contracts\Companies\LenderClients;

use App\Models\CompanyLenderClient;

interface UpdateLenderClient
{
    public function handle(CompanyLenderClient $companyLenderClient, array $data): CompanyLenderClient;
}
