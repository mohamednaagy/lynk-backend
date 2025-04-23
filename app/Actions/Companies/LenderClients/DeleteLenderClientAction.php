<?php

namespace App\Actions\Companies\LenderClients;

use App\Actions\Contracts\Companies\LenderClients\DeleteLenderClient;
use App\Models\CompanyLenderClient;

class DeleteLenderClientAction implements DeleteLenderClient
{
    public function handle(CompanyLenderClient $companyLenderClient): bool
    {
        $companyLenderClient->autoSellPeriods()->delete();

        return $companyLenderClient->delete();
    }
}
