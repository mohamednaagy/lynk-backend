<?php

namespace App\Actions\Wakala;

use App\Actions\Contracts\Wakala\GenerateClientWakala;
use App\Actions\Contracts\Wakala\GetClientWakalaText;
use App\Actions\Contracts\Wakala\GetWakalaTemplate;
use App\Enums\MediaCollections\FinancingOrderMediaCollection;
use App\Models\FinancingOrder;
use App\Support\PdfGenerator\PdfGenerator;

class GenerateClientWakalaAction implements GenerateClientWakala
{
    protected string $template = 'templates.lender-wakala';

    protected string $collectionName = FinancingOrderMediaCollection::LenderWakala;

    protected string $filePath = '';

    public function __construct(
        protected GetWakalaTemplate $getWakalaTemplate,
        protected GetClientWakalaText $getClientWakalaText
    ) {
    }

    public function handle(FinancingOrder $financingOrder)
    {
        $lenderTemplate = $this->getWakalaTemplate->handle('client')['wakala_template'];
        $template = $this->getClientWakalaText->handle($financingOrder, $lenderTemplate);

        $wakalaTemplate = view($this->getTemplate(), [
            'template' => $template,
        ])->render();

        $path = $this->getFilePath($financingOrder).'.pdf';

        return PdfGenerator::outputFromHtml($wakalaTemplate, $path, function ($fileResource) use ($financingOrder) {
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
            return $financingOrder->getKey().'-client-wakala';
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
