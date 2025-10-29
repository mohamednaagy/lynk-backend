<?php

namespace App\Actions\Edaat;

use App\Actions\Contracts\Edaat\GetEdaatInvoices;
use App\Models\Company;
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
    protected ?Company $company = null;

    /**
     * Get edaat invoices for tenant (company) or admin
     */
    public function handle(): Builder
    {
        $query = EdaatInvoice::query();
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

        if ($this->company) {
            $query = $query->where('company_id', $this->company->id);
        }

        return $query->toScopes($scopes);
    }

    /**
     * Set company for query scoping
     */
    public function setCompany(Company $company): self
    {
        $this->company = $company;

        return $this;
    }
}
