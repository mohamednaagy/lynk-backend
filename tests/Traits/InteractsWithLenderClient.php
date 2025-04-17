<?php

namespace Tests\Traits;

use App\Models\ClientAutoSellPeriod;
use App\Models\Company;
use App\Models\CompanyLenderClient;

trait InteractsWithLenderClient
{
    public function createClient(Company $company, array $data = [])
    {
        $data['company_id'] = $company->id;

        return CompanyLenderClient::factory()->create($data);
    }

    public function createPeriodsForClient(CompanyLenderClient $client, array $data = [])
    {
        $data['company_lender_client_id'] = $client->id;

        return ClientAutoSellPeriod::factory()->create($data);
    }
}
