<?php

namespace App\Support\DocumentEngine\Generators;

use App\Enums\MediaCollections\TraderOrderMediaCollection;
use App\Support\DataTransferObjects\LynkCommodityProductDto;
use App\Support\DocumentEngine\BasePdfGenerator;
use App\Support\DocumentEngine\Traits\HasLynkCommodityProducts;
use App\Support\DocumentEngine\Traits\HasTraderOrder;

class SellConfirmationDocumentPdf extends BasePdfGenerator
{
    use HasLynkCommodityProducts, HasTraderOrder;

    protected $collectionName = TraderOrderMediaCollection::SellConfirmationDocument;

    public function getStorageCallback(): callable
    {
        return function ($fileResource) {
            $this->attachDocumentToOrder(
                $fileResource,
                $this->collectionName
            );
        };
    }

    protected function prepareData(): array
    {
        $traderOrder = $this->getTraderOrder();
        $financeOrder = $traderOrder->order;

        $data = [
            'trader_order_reference' => $traderOrder->reference,
            'amount' => $financeOrder->amount->convertAndFormatByDecimal(separator: ','),
            'borrower_name' => $financeOrder->getBorrowerInfo()['name'],
            'current_date' => saudi_now('Y-m-d'),
            'current_time' => saudi_now('H:i:s'),
        ];

        // Handle products based on provider
        if ($traderOrder->provider === 'lynk') {
            $data['products'] = $this->transformProductsToLynkCommodityProductsDTO($traderOrder->products, LynkCommodityProductDto::groupedByKeys());
        } else {
            // For Bursam, use the raw products data or a simpler transformation
            $data['products'] = collect($traderOrder->products)->map(function ($product) {
                return [
                    'product' => $product['product'] ?? '',
                    'quantity' => $product['quantity'] ?? '',
                    'uom' => $product['uom'] ?? '',
                    'amount' => $product['amount'] ?? '',
                    'currency' => $product['currency'] ?? '',
                    'warehouse' => $product['warehouse'] ?? '',
                    'previous_owner' => $product['previous_owner'] ?? [],
                    'date_time_of_purchasing_commodity' => $product['date_time_of_purchasing_commodity'] ?? '',
                ];
            });
        }

        return $data;
    }

    protected function getTemplatePath(): string
    {
        return 'local-commodity-market.sell-confirmation-certificate';
    }
}
