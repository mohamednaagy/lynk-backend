<?php

namespace App\Support\DocumentEngine\Generators;

use App\Support\DocumentEngine\BasePdfGenerator;

class TransferOwnershipToLenderPdf extends BasePdfGenerator
{
    protected function prepareData(array $context): array
    {
        return $context;
    }

    protected function getTemplatePath(): string
    {
        // TODO: will decide the template depends on the provider
        return 'transfer-ownership-to-lender';
    }
}
