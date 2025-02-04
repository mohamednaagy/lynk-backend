<?php

namespace App\Actions\Contracts\Companies;

use App\Models\Lender;

interface CreateCompany
{
    /**
     * @param  array  $data
     * @return Lender
     */
    public function handle(array $data): Lender;
}
