<?php

namespace App\Actions\Contracts\Lenders;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface GetPaginatedLenderUsers
{
    /**
     * @return LengthAwarePaginator
     */
    public function handle(): LengthAwarePaginator;
}
