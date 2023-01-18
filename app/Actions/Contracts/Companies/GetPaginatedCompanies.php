<?php

namespace App\Actions\Contracts\Companies;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface GetPaginatedCompanies
{
    public function handle(): LengthAwarePaginator;

    public function setType(string $type);
}
