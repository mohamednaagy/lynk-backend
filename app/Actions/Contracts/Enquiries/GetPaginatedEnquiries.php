<?php

namespace App\Actions\Contracts\Enquiries;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface GetPaginatedEnquiries
{
    /**
     * @return LengthAwarePaginator
     */
    public function handle(): LengthAwarePaginator;
}
