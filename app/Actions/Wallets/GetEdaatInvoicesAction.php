<?php

// __REVIEW__ move this action to be under app\Actions\Edaat

namespace App\Actions\Wallets;

use App\Actions\Contracts\Wallets\GetEdaatInvoices;
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
    // __REVIEW__ remove $paginate since it is not used
    public function handle(array $scopes = [], int $paginate = 10): Builder
    {
        return EdaatInvoice::query()->toScopes($scopes);
    }
}
