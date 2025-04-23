<?php

namespace App\Actions\Contracts\Companies\LenderClients;

use App\Models\CompanyLenderClient;

interface DeleteLenderClient
{
    public function handle(CompanyLenderClient $companyLenderClient): bool;
}
