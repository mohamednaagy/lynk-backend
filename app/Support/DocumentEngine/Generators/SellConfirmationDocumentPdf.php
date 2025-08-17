<?php

namespace App\Support\DocumentEngine\Generators;

use App\Support\DocumentEngine\BasePdfGenerator;

class SellConfirmationDocumentPdf extends BasePdfGenerator
{
    protected function prepareData(array $context): array
    {
        return $context;
    }

    protected function getTemplatePath(): string
    {
        return 'local-commodity-market.sell-confirmation-certificate';
    }
}
