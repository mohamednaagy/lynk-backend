<?php

namespace App\Actions\Contracts\Wallets;

use App\Support\ZatcaEInvoice\InvoiceSpecs;

interface GenerateZatcaInvoice
{
    public function handle(InvoiceSpecs $invoiceSpecs);
}
