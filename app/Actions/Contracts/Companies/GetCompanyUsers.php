<?php

namespace App\Actions\Contracts\Companies;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface GetCompanyUsers
{
    /**
     * @param  int  $companyId
     * @return LengthAwarePaginator
     */
    public function handle(int $companyId): LengthAwarePaginator;
}
