<?php

namespace App\Actions\Contracts\Lenders;

use Illuminate\Database\Eloquent\Builder;

interface GetEdaatInvoices
{
    /**
     * Get edaat invoices for tenant (company) or admin
     *
     * @param  array|null  $scopes
     * @param  int  $paginate
     * @return  Builder
     */
    public function handle(array $scopes = [], int $paginate = 10): Builder;
}
