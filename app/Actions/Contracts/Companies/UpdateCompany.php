<?php

namespace App\Actions\Contracts\Companies;

use App\Models\Lender;

interface UpdateCompany
{
    public function handle(Lender $lender, array $data): Lender;
}
