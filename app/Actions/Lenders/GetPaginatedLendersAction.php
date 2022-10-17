<?php

namespace App\Actions\Lenders;

use App\Actions\Contracts\Lenders\GetPaginatedLenders;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class GetPaginatedLendersAction implements GetPaginatedLenders
{
    /**
     * @return LengthAwarePaginator
     */
    public function handle(): LengthAwarePaginator
    {
        return User::query()->paginate();
    }
}
