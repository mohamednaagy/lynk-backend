<?php

namespace App\Actions\Contracts\Lenders;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface GetEdaatInvoices
{
    /**
     * Get edaat invoices for tenant (company) or admin
     *
     * @param  array|null  $scopes
     * @param  int  $paginate
     * @return LengthAwarePaginator
     */
    public function handle(array $scopes = null, int $paginate = 10): LengthAwarePaginator;
}
