<?php

namespace App\Actions\Contracts;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface GetPaginatedUsersByRole
{
    /**
     * @param string $role
     * @return LengthAwarePaginator
     */
    public function handle(string $role): LengthAwarePaginator;
}
