<?php

namespace App\Actions\Contracts\Enquiries;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface GetPaginatedEnquiries
{
    /**
     * @param  int|null  $paginate
     * @return LengthAwarePaginator
     */
    public function handle(int $paginate = null): LengthAwarePaginator;
}
