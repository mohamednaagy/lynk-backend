<?php

namespace App\Actions\Wakala;

use App\Actions\Contracts\Wakala\GenerateWakala;
use App\Models\FinancingOrder;
use App\Support\PdfGenerator\PdfGenerator;

class GenerateWakalaAction implements GenerateWakala
{
    const FILE_PATH = 'client_wakala';

    const COLLECTION_NAME = 'client_wakala';

    const BASE_TEMPLATE = 'pdf-template.lender-wakala';

    protected string $template = '';

    protected string $collectionName = '';

    protected string $filePath = '';

    public function handle(FinancingOrder $financingOrder)
    {
        $html = view($this->getTemplate())->render();
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
        if (empty($this->collectionName)) {
            return self::COLLECTION_NAME;
        }

        return $this->collectionName;
    }

    public function getTemplate()
    {
        if (empty($this->template)) {
            return self::BASE_TEMPLATE;
        }

        return $this->template;
    }
}
