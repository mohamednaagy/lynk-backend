<?php

namespace App\Support\ZatcaEInvoice;

use Cknow\Money\Money;
use function Psl\Str\format_number;

class PurchaseLine
{
    public function __construct(
        protected string $name,
        protected Money $itemPrice,
        protected string $vatPercentage,
        protected float $discount = 0,
        protected int|float $quantity = 1
    ) {
    }

    public static function fromArray(array $data): PurchaseLine
    {
        return new static(
            $data['name'],
            $data['itemPrice'],
            $data['vatPercentage'],
            $data['discount'],
            $data['quantity'],
        );
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getQuantity(): string
    {
        return format_number($this->quantity, 2);
    }

    public function getItemPrice(): Money
    {
        return $this->itemPrice;
    }

    public function getVatPercentage(): string
    {
        return $this->vatPercentage;
    }

    public function getDiscountPercentage(): string
    {
        return $this->discount;
    }

    /**
     * Get total line amount (without VAT & without discount)
     */
    public function getLineSubtotal(): Money
    {
        return $this->itemPrice->multiply($this->quantity);
    }

    public function getTotalDiscountAmount(): Money|array
    {
        if (! $this->discount) {
            return money(0, $this->itemPrice->getCurrency()->getCode());
        }

        return $this->getLineSubtotal()
            ->multiply($this->discount)
            ->divide(100);
    }

    public function getLineTotalWithoutVat()
    {
        return $this->getLineSubtotal();
    }

    /**
     * Get total VAT amount
     */
    public function getTotalVatAmount(): Money
    {
        if (! $this->vatPercentage) {
            return money(0, $this->itemPrice->getCurrency()->getCode());
        }

        return $this->getLineSubtotal()
            ->subtract($this->getTotalDiscountAmount())
            ->multiply($this->vatPercentage)
            ->divide(100);
    }

    /**
     * Get total line amount (with VAT)
     */
    public function getLineTotal(): Money
    {
        return $this->getLineSubtotal()
            ->subtract($this->getTotalDiscountAmount())
            ->add($this->getTotalVatAmount());
    }
}
