<?php

namespace App\Support\DocumentEngine\Generators;

use App\Enums\FinancingOrderHistory;
use App\Enums\MediaCollections\TraderOrderMediaCollection;
use App\Enums\Trader;
use App\Support\DataTransferObjects\LynkCommodityProductDto;
use App\Support\DocumentEngine\BasePdfGenerator;
use App\Support\DocumentEngine\Traits\HasLynkCommodityProducts;
use App\Support\DocumentEngine\Traits\HasTraderOrder;

class SellingCommodityToCustomerPdf extends BasePdfGenerator
{
    use HasLynkCommodityProducts, HasTraderOrder;

    protected $collectionName = TraderOrderMediaCollection::SellingCommodityToCustomer;

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
        $amount = $traderOrder->order->selling_price->convertAndFormatByDecimal(sperator: ',');
        $lenderName = $traderOrder->order->getLenderInfo()['name'];
        $borrowerName = $traderOrder->order->getBorrowerInfo()['name'];
        $currentTimeInRiyadhTz = $traderOrder->traderHistories()
            ->where('action', FinancingOrderHistory::CreateSellingCommodityToCustomerDocument)
            ->first()->created_at;

        $data = [
            'reference_number' => $traderOrder->id,
            'trader_order_reference' => $traderOrder->reference,
            'order_number' => $traderOrder->financing_order_id,
            'amount' => $amount,
            'lender_name' => $lenderName,
            'borrower_name' => $borrowerName,
            'contract_signed_date' => saudi_now('Y-m-d', $currentTimeInRiyadhTz),
            'contract_signed_time' => saudi_now('h:i:s A', $currentTimeInRiyadhTz),
        ];

        // Handle products based on provider
        if ($traderOrder->provider === Trader::Lynk) {
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
        $traderOrder = $this->getTraderOrder();
        if ($traderOrder->provider === Trader::Lynk) {
            return 'local-commodity-market.selling-commodity-to-customer';
        }

        if ($traderOrder->provider === Trader::Bursam) {
            return 'selling-commodity-to-customer';
        }

        throw new \Exception('Trader order provider not found');
    }
}
