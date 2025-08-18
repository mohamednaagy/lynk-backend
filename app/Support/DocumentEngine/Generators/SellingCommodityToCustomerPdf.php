<?php

namespace App\Support\DocumentEngine\Generators;

use App\Enums\MediaCollections\TraderOrderMediaCollection;
use App\Enums\Trader;
use App\Support\DataTransferObjects\LynkCommodityProductDto;
use App\Support\DocumentEngine\BasePdfGenerator;
use App\Support\DocumentEngine\Traits\HasLynkCommodityProducts;
use App\Support\DocumentEngine\Traits\HasTraderOrder;
use Carbon\CarbonImmutable;

class SellingCommodityToCustomerPdf extends BasePdfGenerator
{
    use HasLynkCommodityProducts, HasTraderOrder;

    protected $collectionName = TraderOrderMediaCollection::SellingCommodityToCustomer;

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
        $amount = $traderOrder->order->selling_price->convertAndFormatByDecimal(sperator: ',');
        $customerName = $traderOrder->order->customer_name;
        $currentTimeInRiyadhTz = CarbonImmutable::now()->timezone('Asia/Riyadh');

        return [
            'reference_number' => $traderOrder->id,
            'trader_order_reference' => $traderOrder->reference,
            'company_name' => $traderOrder->order->company()->withTrashed()->first()->name,
            'order_number' => $traderOrder->financing_order_id,
            'products' => $this->transformProductsToLynkCommodityProductsDTO($traderOrder->products, LynkCommodityProductDto::groupedByKeys()),
            'amount' => $amount,
            'customer_name' => $customerName,
            'contract_signed_date' => $currentTimeInRiyadhTz->toDateString(),
            'contract_signed_time' => $currentTimeInRiyadhTz->toTimeString(),
        ];
    }

    protected function getTemplatePath(): string
    {
        $traderOrder = $this->getTraderOrder();
        if ($traderOrder->provider->isTrader(Trader::Lynk)) {
            return 'local-commodity-market.selling-commodity-to-customer';
        }

        if ($traderOrder->provider->isTrader(Trader::Bursam)) {
            return 'selling-commodity-to-customer';
        }

        throw new \Exception('Trader order provider not found');
    }
}
