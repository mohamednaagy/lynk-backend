<?php

namespace App\Actions\Wakala;

use App\Actions\Contracts\Wakala\GenerateLenderWakala;
use App\Enums\MediaCollections\FinancingOrderMediaCollection;
use App\Models\FinancingOrder;
use App\Support\PdfGenerator\PdfGenerator;

class GenerateLenderWakalaAction implements GenerateLenderWakala
{
    const FILE_PATH = 'lender_wakala';

    protected string $template = 'templates.lender-wakala';

    protected string $collectionName = FinancingOrderMediaCollection::LenderWakala;

    protected string $filePath = '';

    public function handle(FinancingOrder $financingOrder)
    {
        $html = view($this->getTemplate(), [
            'companyName' => $financingOrder->company->name,
            'crNumber' => $financingOrder->company->company_cr,
            'header' => 'Header',
            'footer' => 'Footer',
        ])->render();

        $path = $this->getFilePath($financingOrder).'.pdf';
        PdfGenerator::outputFromHtml($html, $path);

        return $financingOrder
            ->addMediaFromDisk($path)
            ->toMediaCollection($this->getCollectionName());
    }

    public function setTemplate(string $template)
    {
        $this->template = $template;

        return $this;
    }

    public function setFilePath(string $filePath)
    {
        $this->filePath = $filePath;

        return $this;
    }

    public function setCollectionName(string $collectionName)
    {
        $this->collectionName = $collectionName;

        return $this;
    }

    public function getFilePath(FinancingOrder $financingOrder)
    {
        if (empty($this->filePath)) {
            return $financingOrder->getKey().DIRECTORY_SEPARATOR.self::FILE_PATH.DIRECTORY_SEPARATOR.$financingOrder->getNationalId();
        }

        return $this->filePath;
    }

    public function getCollectionName()
    {
        return $this->collectionName;
    }

    public function getTemplate()
    {
        return $this->template;
    }
}
