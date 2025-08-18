<?php

namespace App\Support\DocumentEngine\Generators;

use App\Actions\Contracts\Wakala\GetClientWakalaText;
use App\Actions\Contracts\Wakala\GetWakalaTemplate;
use App\Enums\MediaCollections\TraderOrderMediaCollection;
use App\Support\DocumentEngine\BasePdfGenerator;
use App\Support\DocumentEngine\Traits\HasTraderOrder;

class ClientWakalaPdf extends BasePdfGenerator
{
    use HasTraderOrder;

    protected $collectionName = TraderOrderMediaCollection::ClientWakala;

    public function getStorageCallback(): callable
    {
        return function ($fileResource) {
            return $this->getTraderOrder()
                ->addMediaFromStream($fileResource)
                ->usingFileName($this->getTraderOrder()->order->getNationalId().'.pdf')
                ->toMediaCollection($this->collectionName);
        };
    }

    public function isGeneratedBefore(): bool
    {
        return $this->getTraderOrder()->hasMedia($this->collectionName);
    }

    public function getGeneratedBeforePath(): string
    {
        return $this->getTraderOrder()->getMedia($this->collectionName)->first()->getPath();
    }

    protected function prepareData()
    {
        $getWakalaTemplate = app(GetWakalaTemplate::class);
        $getClientWakalaText = app(GetClientWakalaText::class);
        $lenderTemplate = $getWakalaTemplate->handle('client')['wakala_template'];
        $template = $getClientWakalaText->handle($this->getTraderOrder(), $lenderTemplate);

        return [
            'template' => $template,
        ];
    }

    protected function getTemplatePath(): string
    {
        return 'templates.client-wakala';
    }
}
