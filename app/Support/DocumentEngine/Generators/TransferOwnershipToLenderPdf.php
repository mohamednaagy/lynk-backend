<?php

namespace App\Support\DocumentEngine\Generators;

use App\Enums\FinancingOrderHistory;
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
        $financingOrder = $traderOrder->order;
        $products = collect($traderOrder->products); // Convert to Collection
        $currentTimeInRiyadhTz = $traderOrder->traderHistories()->where('action', FinancingOrderHistory::CreateTransferOwnershipToLenderDocument)->first()->created_at;

        $data = [
            'order_id' => $financingOrder->id,
            'reference_number' => $traderOrder->id,
            'trader_order_reference' => $traderOrder->reference,
            'lender_name' => $traderOrder->order->getLenderInfo()['name'],
            'order_number' => $traderOrder->financing_order_id,
            'amount' => $financingOrder->amount->convertAndFormatByDecimal(separator: ','),
            'previous_owner' => $products->map(
                fn ($item) => $item['previous_owner'] ?? []
            )
                ->flatten()
                ->filter()
                ->implode('،'),
            'product_name' => $products->pluck('product')->filter()->implode('،'),
            'date' => saudi_now('Y-m-d', $currentTimeInRiyadhTz),
            'time' => saudi_now('h:i:s A', $currentTimeInRiyadhTz),
        ];

        if ($traderOrder->provider === Trader::Lynk) {
            $data['products'] = $this->transformProductsToLynkCommodityProductsDTO($traderOrder->products);
            $data['trade_order'] = $traderOrder;
            $data['financing_order'] = $financingOrder;
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
