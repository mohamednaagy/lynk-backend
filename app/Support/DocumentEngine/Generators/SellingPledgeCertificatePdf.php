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
                $fileResource,
                $this->collectionName
            );
        };
    }

    protected function prepareData(): array
    {
        $traderOrder = $this->getTraderOrder();
        $financeOrder = $traderOrder->order;
        $currentTimeInRiyadhTz = CarbonImmutable::now()->timezone('Asia/Riyadh');

        $data = [
            'trader_order_reference' => $traderOrder->reference,
            'amount' => $financeOrder->amount->convertAndFormatByDecimal(sperator: ','),
            'customer_name' => $financeOrder->customer_name,
            'current_date' => $currentTimeInRiyadhTz->toDateString(),
            'current_time' => $currentTimeInRiyadhTz->toTimeString(),
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
        return 'local-commodity-market.selling-pledge-certificate';
    }
}
