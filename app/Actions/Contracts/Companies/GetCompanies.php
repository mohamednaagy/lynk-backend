<?php

namespace App\Actions\Contracts\Companies;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface GetCompanies
{
    public function handle(): LengthAwarePaginator;
}
