<?php

namespace App\Actions\Contracts\Lenders;

use App\Models\Company;

interface UpdateLenderSettings
{
    public function handle(Company $company, array $data): void;
}
