<?php

namespace App\Actions\Contracts\Edaat;

use Illuminate\Database\Eloquent\Builder;

interface GetEdaatInvoices
{
    /**
     * Get edaat invoices for tenant (company) or admin
     */
    public function handle(): Builder;
}
