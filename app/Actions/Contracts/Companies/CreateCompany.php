<?php

namespace App\Actions\Contracts\Companies;

use App\Models\Lender;

interface CreateCompany
{
    public function handle(array $data): Lender;
}
