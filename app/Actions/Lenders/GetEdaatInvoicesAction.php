<?php

namespace App\Actions\Lenders;

use App\Actions\Contracts\Lenders\GetEdaatInvoices;
use App\Models\EdaatInvoice;
use Illuminate\Database\Eloquent\Builder;

class GetEdaatInvoicesAction implements GetEdaatInvoices
{
    /**
     * Get edaat invoices for tenant (company) or admin
     *
     * @param  array  $scopes
     * @param  int  $paginate
     * @return  Builder
     */
    public function handle(array $scopes = [], int $paginate = 10): Builder
    {
        return EdaatInvoice::query()->toScopes($scopes);
    }
}
