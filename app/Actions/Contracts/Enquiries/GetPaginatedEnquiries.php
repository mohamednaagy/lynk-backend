<?php

namespace App\Actions\Contracts\Enquiries;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface GetPaginatedEnquiries
{
    public function handle(?int $paginate = null): LengthAwarePaginator;
}
