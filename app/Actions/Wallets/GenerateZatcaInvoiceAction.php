<?php

namespace App\Actions\Wallets;

use App\Actions\Contracts\Wallets\GenerateZatcaInvoice;
use App\Support\DocumentEngine\PdfFactory;
use App\Support\ZatcaEInvoice\InvoiceSpecs;

class GenerateZatcaInvoiceAction implements GenerateZatcaInvoice
{
    public function handle(InvoiceSpecs $invoiceSpecs)
    {
        $pdf = PdfFactory::make('zatca_invoice');
        $pdf->setContext([
            'invoiceSpecs' => $invoiceSpecs,
        ]);
        $pdf->generate();
    }
}
