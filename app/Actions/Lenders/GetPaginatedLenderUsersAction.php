<?php

namespace App\Actions\Lenders;

use App\Actions\Contracts\Lenders\GetPaginatedLenderUsers;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class GetPaginatedLenderUsersAction implements GetPaginatedLenderUsers
{
    /**
     * @return LengthAwarePaginator
     */
    public function handle(): LengthAwarePaginator
    {
        return User::query()->paginate();
    }
}
