<?php

namespace App\Support\DocumentEngine\Generators;

use App\Support\DocumentEngine\BasePdfGenerator;

class LenderWakalaPdf extends BasePdfGenerator
{
    protected function prepareData(array $context): array
    {
        return $context;
    }

    protected function getTemplatePath(): string
    {
        return 'pdf.lender_wakala';
    }
}
