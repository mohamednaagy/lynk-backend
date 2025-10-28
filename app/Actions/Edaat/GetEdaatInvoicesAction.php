<?php

namespace App\Actions\Edaat;

use App\Actions\Contracts\Edaat\GetEdaatInvoices;
use App\Models\EdaatInvoice;
use App\Support\QueryScoper\Scopes\Edaat\InvoiceAmountGteScope;
use App\Support\QueryScoper\Scopes\Edaat\InvoiceAmountLteScope;
use App\Support\QueryScoper\Scopes\Edaat\InvoiceCompaniesScope;
use App\Support\QueryScoper\Scopes\Edaat\InvoiceDateFromScope;
use App\Support\QueryScoper\Scopes\Edaat\InvoiceDateToScope;
use App\Support\QueryScoper\Scopes\Edaat\InvoiceNumberScope;
use App\Support\QueryScoper\Scopes\Edaat\InvoiceSortByCreatedAtScope;
use App\Support\QueryScoper\Scopes\Edaat\InvoiceStatusScope;
use Illuminate\Database\Eloquent\Builder;

class GetEdaatInvoicesAction implements GetEdaatInvoices
{
    /**
     * Get edaat invoices for tenant (company) or admin
     */
    public function handle(): Builder
    {
        $scopes = [
            // Common filters
            'invoice_number' => InvoiceNumberScope::class,
            'status' => InvoiceStatusScope::class,
            'date_from' => InvoiceDateFromScope::class,
            'date_to' => InvoiceDateToScope::class,
            'amount_gte' => InvoiceAmountGteScope::class,
            'amount_lte' => InvoiceAmountLteScope::class,
            'sort_by_created_at' => InvoiceSortByCreatedAtScope::class,

            // Admin-only company filters (single or multiple via InvoiceCompaniesScope)
            'company' => InvoiceCompaniesScope::class,
        ];

        return EdaatInvoice::query()->toScopes($scopes);
    }
}
