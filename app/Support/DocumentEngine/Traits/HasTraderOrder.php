<?php

namespace App\Support\DocumentEngine\Traits;

use App\Support\Traders\TraderManager;

trait HasTraderOrder
{
    private $traderOrder = null;

    public function isTraderOrderInContext(): bool
    {
        return isset($this->context['traderOrder']) && $this->context['traderOrder'] !== null;
    }

    public function getTraderOrder()
    {
        if (! $this->isTraderOrderInContext()) {
            throw new \Exception('Trader order not found in context');
        }

        if ($this->traderOrder === null) {
            $this->traderOrder = $this->getTraderOrderFromContext();
        }

        return $this->traderOrder;
    }

    private function getTraderOrderFromContext()
    {
        return $this->context['traderOrder'];
    }

    public function attachDocumentToOrder($document, $collectionName, $type = null, $originalFileName = null): void
    {
        $traderManager = new TraderManager(app());
        $fileName = $originalFileName ?? $traderManager->driver($this->getTraderOrder()->provider)->generatePdfFileName($this->getTraderOrder(), $collectionName);
        $media = $type
            ? $this->getTraderOrder()->addMediaFromBase64($document)
            : $this->getTraderOrder()->addMediaFromStream($document);

        $media->usingFileName($fileName)->toMediaCollection($collectionName);
    }
}
