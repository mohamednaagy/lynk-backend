<?php

namespace Tests\Traits;

use App\Models\Company;
use App\Models\CompanyLenderClient;

trait InteractsWithLenderClient
{
    public function createClient(Company $company, array $data = [])
    {
        return CompanyLenderClient::factory()->create([
            'company_id' => $company->id,
        ]);
    }
}
