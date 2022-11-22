<?php

namespace App\Actions\Wakala;

use App\Actions\Contracts\Wakala\GenerateClientWakala;
use App\Enums\MediaCollections\FinancingOrderMediaCollection;
use App\Models\FinancingOrder;
use App\Support\PdfGenerator\PdfGenerator;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class GenerateClientWakalaAction implements GenerateClientWakala
{
    const FILE_PATH = 'client_wakala';

    protected string $template = 'templates.client-wakala';

    protected string $collectionName = FinancingOrderMediaCollection::ClientWakala;

    protected string $filePath = '';

    /**
     * @param  FinancingOrder  $financingOrder
     * @return Media
     */
    public function handle(FinancingOrder $financingOrder): Media
    {
        $html = view($this->getTemplate(), [
            'clientName' => $financingOrder->company->name,
        ])->render();

        $path = $this->getFilePath($financingOrder).'.pdf';

        return PdfGenerator::outputFromHtml($html, $path, function ($fileResource) use ($financingOrder) {
            return $financingOrder->addMediaFromStream($fileResource)
                ->usingFileName($financingOrder->getNationalId().'.pdf')
                ->toMediaCollection($this->getCollectionName());
        });
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
