<?php

namespace App\Actions\Contracts\Lenders;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface GetPaginatedLenders
{
    /**
     * @return LengthAwarePaginator
     */
    public function handle(): LengthAwarePaginator;
}
