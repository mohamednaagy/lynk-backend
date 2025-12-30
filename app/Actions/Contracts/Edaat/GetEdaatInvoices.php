<?php

namespace App\Actions\Contracts\Edaat;

use App\Models\Lender;
use Illuminate\Database\Eloquent\Builder;

interface GetEdaatInvoices
{
    /**
     * Get edaat invoices for tenant (company) or admin
     */
    public function handle(): Builder;

    /**
     * Set lender for query scoping
     */
    public function setLender(Lender $lender): self;
}
