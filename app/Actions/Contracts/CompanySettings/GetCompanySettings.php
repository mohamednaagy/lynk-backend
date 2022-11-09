<?php

namespace App\Actions\Contracts\CompanySettings;

use App\Support\CompanySettings\Company;

interface GetCompanySettings
{
    public function handle(): Company;
}
