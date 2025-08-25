<?php

namespace App\Support\DocumentEngine\Traits;

use App\Support\ZatcaEInvoice\InvoiceSpecs;

trait HasInvoiceSpecs
{
    public function getInvoiceSpecs(): InvoiceSpecs
    {
        return $this->context['invoiceSpecs'] ?? null;
    }
}
