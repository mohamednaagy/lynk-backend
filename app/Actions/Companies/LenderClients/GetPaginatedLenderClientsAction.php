<?php

namespace App\Actions\Companies\LenderClients;

use App\Actions\Contracts\Companies\LenderClients\GetPaginatedLenderClients;
use App\Models\Company;
use App\Models\CompanyLenderClient;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class GetPaginatedLenderClientsAction implements GetPaginatedLenderClients
{
    public function handle(Company $lender): LengthAwarePaginator
    {
        return CompanyLenderClient::query()
            ->where('company_id', $lender->id)
            ->orderBy('id', 'desc')
            ->paginate();
    }
}
