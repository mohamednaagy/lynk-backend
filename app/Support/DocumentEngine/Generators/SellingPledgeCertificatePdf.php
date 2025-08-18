<?php

namespace App\Support\DocumentEngine\Generators;

use App\Enums\MediaCollections\TraderOrderMediaCollection;
use App\Support\DataTransferObjects\LynkCommodityProductDto;
use App\Support\DocumentEngine\BasePdfGenerator;
use App\Support\DocumentEngine\Traits\HasLynkCommodityProducts;
use App\Support\DocumentEngine\Traits\HasTraderOrder;
use Carbon\CarbonImmutable;

class SellingPledgeCertificatePdf extends BasePdfGenerator
{
    use HasLynkCommodityProducts, HasTraderOrder;

    protected $collectionName = TraderOrderMediaCollection::LynkSalePledgeCertificate;

    public function getStorageCallback(): callable
    {
        return function ($fileResource) {
            $this->attachDocumentToOrder(
                $this->getTraderOrder(),
                $fileResource,
                $this->collectionName
            );
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

    protected function prepareData(): array
    {
        $traderOrder = $this->getTraderOrder();
        $financeOrder = $traderOrder->order;
        $currentTimeInRiyadhTz = CarbonImmutable::now()->timezone('Asia/Riyadh');

        return [
            'products' => $this->transformProductsToLynkCommodityProductsDTO($traderOrder->products, LynkCommodityProductDto::groupedByKeys()),
            'trader_order_reference' => $traderOrder->reference,
            'amount' => $financeOrder->amount->convertAndFormatByDecimal(sperator: ','),
            'customer_name' => $financeOrder->customer_name,
            'current_date' => $currentTimeInRiyadhTz->toDateString(),
            'current_time' => $currentTimeInRiyadhTz->toTimeString(),
        ];
    }

    protected function getTemplatePath(): string
    {
        return 'local-commodity-market.selling-pledge-certificate';
    }
}
