<?php

namespace App\Actions\Contracts\Enquiries;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface GetPaginatedUserEnquiries
{
    /**
     * Create new enquiry.
     *
     * @param  int  $userId
     * @return LengthAwarePaginator
     */
    public function handle(int $userId): LengthAwarePaginator;
}
