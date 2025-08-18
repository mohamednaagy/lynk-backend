<?php

namespace App\Support\DocumentEngine\Generators;

use App\Support\DocumentEngine\BasePdfGenerator;
use App\Support\DocumentEngine\Traits\HasTraderOrder;

class ClientWakalaPdf extends BasePdfGenerator
{
    use HasTraderOrder;

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

    protected function prepareData()
    {
        return $this->context;
    }

    protected function getTemplatePath(): string
    {
        return 'pdf.client_wakala';
    }
}
