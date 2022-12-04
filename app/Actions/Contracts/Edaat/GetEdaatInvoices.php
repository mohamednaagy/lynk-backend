<?php

namespace App\Actions\Contracts\Edaat;

use Illuminate\Database\Eloquent\Builder;

interface GetEdaatInvoices
{
    /**
     * Get edaat invoices for tenant (company) or admin
     *
     * @param  array  $scopes
     * @return  Builder
     */
    public function handle(array $scopes = []): Builder;
}
