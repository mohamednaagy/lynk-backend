<?php

namespace App\Actions\Contracts\Lenders;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface GetPaginatedLenderUsers
{
    public function handle(): LengthAwarePaginator;
}
