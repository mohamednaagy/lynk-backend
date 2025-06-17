<?php

namespace App\Actions\Contracts\Lenders;

use App\Models\Company;

interface GetValidCommodityTypes
{
    public function handle(Company $company): array;
}
