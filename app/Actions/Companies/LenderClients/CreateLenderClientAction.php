<?php

namespace App\Actions\Companies\LenderClients;

use App\Actions\Contracts\Companies\LenderClients\CreateLenderClient;
use App\Models\Company;
use App\Models\CompanyLenderClient;
use Illuminate\Support\Arr;

class CreateLenderClientAction implements CreateLenderClient
{
    public function handle(Company $lender, array $data): CompanyLenderClient
    {
        $data['company_id'] = $lender->id;

        return CompanyLenderClient::create(
            Arr::only(
                $data,
                [
                    'name',
                    'type',
                    'national_id',
                    'company_id',
                ]
            )
        );
    }
}
