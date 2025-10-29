<?php

namespace App\Actions\Contracts\Edaat;

use App\Models\Company;
use Illuminate\Database\Eloquent\Builder;

interface GetEdaatInvoices
{
    /**
     * Get edaat invoices for tenant (company) or admin
     */
    public function handle(): Builder;

    /**
     * Set company for query scoping
     */
    public function setCompany(Company $company): self;
}
