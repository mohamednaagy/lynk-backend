<?php

namespace App\Support\DocumentEngine\Generators;

use App\Support\DocumentEngine\BasePdfGenerator;

class TraderOrderZatcaInvoicePdf extends BasePdfGenerator
{
    protected function prepareData(array $context): array
    {
        return $context;
    }

    protected function getTemplatePath(): string
    {
        return 'pdf.trader_order_zatca_invoice';
    }
}
