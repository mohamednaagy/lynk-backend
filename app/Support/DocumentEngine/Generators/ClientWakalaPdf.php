<?php

namespace App\Support\DocumentEngine\Generators;

use App\Support\DocumentEngine\BasePdfGenerator;

class ClientWakalaPdf extends BasePdfGenerator
{
    protected function prepareData(array $context): array
    {
        return $context;
    }

    protected function getTemplatePath(): string
    {
        return 'pdf.client_wakala';
    }
}
