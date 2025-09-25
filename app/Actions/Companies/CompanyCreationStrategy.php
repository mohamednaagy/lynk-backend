<?php

namespace App\Actions\Companies;

use App\Enums\CompanyType;
use App\Models\Lender;

interface CompanyCreationStrategy
{
    public function create(array $data): Lender;
}