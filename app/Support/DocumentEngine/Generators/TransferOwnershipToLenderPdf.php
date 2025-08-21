<?php

namespace App\Support\DocumentEngine\Generators;

use App\Enums\MediaCollections\TraderOrderMediaCollection;
use App\Enums\Trader;
use App\Support\DocumentEngine\BasePdfGenerator;
use App\Support\DocumentEngine\Traits\HasLynkCommodityProducts;
use App\Support\DocumentEngine\Traits\HasProducts;
use App\Support\DocumentEngine\Traits\HasTraderOrder;

class TransferOwnershipToLenderPdf extends BasePdfGenerator
{
    use HasLynkCommodityProducts, HasProducts, HasTraderOrder;

    protected $collectionName = TraderOrderMediaCollection::TransferOwnershipToLender;

    protected function prepareData()
    {
        $traderOrder = $this->getTraderOrder();
        $products = collect($traderOrder->products); // Convert to Collection
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
                fn ($item) => $item['previous_owner'] ?? []
            )
                ->flatten()
                ->filter()
                ->implode('،'),
            'product_name' => $products->pluck('product')->filter()->implode('،'),
            'date' => $currentTimeInRiyadhTz->toDateString(),
            'time' => $currentTimeInRiyadhTz->toTimeString(),
        ];

        if ($traderOrder->provider === Trader::Lynk) {
            $data['products'] = $this->transformProductsToLynkCommodityProductsDTO($traderOrder->products);
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
        if ($traderOrder->provider === Trader::Lynk) {
            return 'local-commodity-market.transfer-ownership-to-lender';
        }

        if ($traderOrder->provider === Trader::Bursam) {
            return 'transfer-ownership-to-lender';
        }

        throw new \Exception('Trader order provider not found');
    }

    protected function getStorageCallback(): callable
    {
        return function ($fileResource) {
            $this->attachDocumentToOrder($fileResource, $this->collectionName);
        };
    }
}
