<?php

namespace App\Support\DocumentEngine\Generators;

use App\Enums\Trader;
use App\Support\DocumentEngine\BasePdfGenerator;
use App\Support\DocumentEngine\Traits\HasProducts;
use App\Support\DocumentEngine\Traits\HasTraderOrder;

class TransferOwnershipToLenderPdf extends BasePdfGenerator
{
    use HasProducts, HasTraderOrder;

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
        $traderOrder = $this->getTraderOrder();
        $products = $traderOrder->products;
        $amount = $traderOrder->amount;
        $currentTimeInRiyadhTz = now('Asia/Riyadh');

        $data = [
            'order_id' => $traderOrder->order->id,
            'reference_number' => $traderOrder->id,
            'trader_order_reference' => $traderOrder->reference,
            'company_name' => $traderOrder->order->company()->withTrashed()->first()->name,
            'order_number' => $traderOrder->financing_order_id,
            'amount' => $amount,
            'previous_owner' => $products->map(
                fn ($item) => $item->getPreviousOwnerAsArray()
            )
                ->flatten()
                ->implode('،'),
            'product_name' => $products->implode(fn ($item) => $item->getProduct(), '،'),
            'date' => $currentTimeInRiyadhTz->toDateString(),
            'time' => $currentTimeInRiyadhTz->toTimeString(),
        ];

        if ($traderOrder->provider->isTrader(Trader::Lynk)) {
            $data['products'] = $this->transformProductsToLocalCommodityProductsDTO($traderOrder->products);
            $data['trade_order'] = $traderOrder;
            $data['financing_order'] = $traderOrder->order;
        } else {
            $data['products'] = $this->transformProductsToCommodityProductsDTO($traderOrder->products);
        }

        return $data;
    }

    protected function getTemplatePath(): string
    {
        $traderOrder = $this->getTraderOrder();
        if ($traderOrder->provider->isTrader(Trader::Lynk)) {
            return 'local-commodity-market.transfer-ownership-to-lender';
        }

        if ($traderOrder->provider->isTrader(Trader::Bursam)) {
            return 'transfer-ownership-to-lender';
        }

        throw new \Exception('Trader order provider not found');
    }
}
