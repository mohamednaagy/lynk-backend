<?php

namespace App\Support\DocumentEngine\Generators;

use App\Support\DocumentEngine\BasePdfGenerator;

class SellConfirmationDocumentPdf extends BasePdfGenerator
{
    public function getStorageCallback(): callable
    {
        return function ($path) {
            return storage_path($path);
        };
    }

    public function isGeneratedBefore(): bool
    {
        return false;
    }

    public function getGeneratedBeforePath(): string
    {
        return '';
    }

    protected function prepareData(): array
    {
        return [];
    }

    protected function getTemplatePath(): string
    {
        return 'local-commodity-market.sell-confirmation-certificate';
    }
}
