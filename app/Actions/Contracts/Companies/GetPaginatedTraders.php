<?php

namespace App\Actions\Contracts\Companies;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface GetPaginatedTraders
{
    public function handle(): LengthAwarePaginator;
}
