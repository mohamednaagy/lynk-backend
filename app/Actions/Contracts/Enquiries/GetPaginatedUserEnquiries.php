<?php

namespace App\Actions\Contracts\Enquiries;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface GetPaginatedUserEnquiries
{
    /**
     * Create new enquiry.
     */
    public function handle(int $userId): LengthAwarePaginator;
}
