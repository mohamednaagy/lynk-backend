<?php

namespace App\Support\ZatcaEInvoice;

use App\Models\FinancingOrder;
use Cknow\Money\Money;
use Illuminate\Support\Carbon;

class Order
{
    public function __construct(
        protected string $reference,
        protected array $items,
        protected Carbon $invoiceDate,
        protected FinancingOrder $order,
    ) {
    }

    public static function fromArray(array $data): Order
    {
        return new static(
            $data['reference'],
            $data['items'],
            $data['invoiceDate'],
            $data['order']
        );
    }

    public function getItems(): array
    {
        return $this->items;
    }

    /**
     * Get order date
     *
     * @return \Carbon\Carbon
     */
    public function getInvoiceDate(): Carbon|\Carbon\Carbon
    {
        return $this->invoiceDate;
    }

    /**
     * Get order subtotal (without VAT or without discount)
     *
     * @return Money
     */
    public function getSubtotal(): Money
    {
        return collect($this->getItems())->reduce(
            function ($carry, $item) {
                return $carry->add($item->getLineSubtotal());
            },
            money(0)
        );
    }

    /**
     * Get order total amount
     *
     * @return Money
     */
    public function getTotalAmount(): Money
    {
        return collect($this->getItems())->reduce(
            function ($carry, $item) {
                return $carry->add($item->getLineTotal());
            },
            money(0)
        );
    }

    /**
     * Get order total discount
     *
     * @return Money
     */
    public function getTotalDiscount(): Money
    {
        return collect($this->getItems())->reduce(
            function ($carry, $item) {
                return $carry->add($item->getTotalDiscountAmount());
            },
            money(0)
        );
    }

    /**
     * Get order total amount without VAT
     *
     * @return Money
     */
    public function getTotalWithoutVat(): Money
    {
        return $this->getSubtotal()->subtract($this->getTotalDiscount());
    }

    /**
     * Get order total VAT
     *
     * @return Money
     */
    public function getTotalVat(): Money
    {
        return collect($this->getItems())->reduce(
            function ($carry, $item) {
                return $carry->add($item->getTotalVatAmount());
            },
            money(0)
        );
    }
}
