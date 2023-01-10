<?php

namespace App\Actions\Contracts\Users;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface GetPaginatedUsers
{
    /**
     * @param $companyType
     * @param  array|null  $role
     * @return LengthAwarePaginator
     */
    public function handle($companyType, array $role = null): LengthAwarePaginator;
}
