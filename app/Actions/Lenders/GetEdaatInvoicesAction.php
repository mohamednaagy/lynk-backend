<?php

namespace App\Actions\Lenders;

use App\Actions\Contracts\Lenders\GetEdaatInvoices;
use App\Models\EdaatInvoice;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class GetEdaatInvoicesAction implements GetEdaatInvoices
{
    /**
     * Get edaat invoices for tenant (company) or admin
     *
     * @param  array|null  $scopes
     * @param  int  $paginate
     * @return LengthAwarePaginator
     */
    public function handle(array $scopes = null, int $paginate = 10): LengthAwarePaginator
    {
        $edaatInvoices = EdaatInvoice::with(['company', 'creator']);

        if ($scopes) {
            $edaatInvoices->toScopes($scopes);
        }

        return $edaatInvoices->paginate($paginate);
    }
}
