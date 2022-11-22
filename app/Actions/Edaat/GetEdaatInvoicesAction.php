<?php

namespace App\Actions\Edaat;

use App\Actions\Contracts\Edaat\GetEdaatInvoices;
use App\Models\EdaatInvoice;
use Illuminate\Database\Eloquent\Builder;

class GetEdaatInvoicesAction implements GetEdaatInvoices
{
    /**
     * Get edaat invoices for tenant (company) or admin
     *
     * @param  array  $scopes
     * @return  Builder
     */
    public function handle(array $scopes = []): Builder
    {
        return EdaatInvoice::query()->toScopes($scopes);
    }
}
