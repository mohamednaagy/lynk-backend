<?php

namespace App\Actions\Contracts\CompanySettings;

use App\Support\CompanySettings\Company;

interface UpdateCompanySettings
{
    public function handle(array $data): Company;
}
