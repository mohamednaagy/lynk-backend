<?php

namespace App\Support\DataTransferObjects;

use App\Enums\BursamProductCode;

class CommodityProductDto
{
    public function __construct(
        protected string $product,
        protected string $quantity,
        protected float $amount,
        protected string $previous_owner,
        protected string $date_time_of_purchasing_commodity,
        protected ?string $product_code = null,
        protected string $uom = '--',
        protected ?string $warehouse = null,
        protected string $warehouse_or_vault_emirates = '--',
        protected string $warehouse_or_vault_country = '--',
        protected string $currency = 'SAR',
        protected string $exchange_rate = '1',
    ) {
    }

    public function getProduct(): string
    {
        $productCode = $this->product_code ?? $this->product;

        return in_array($productCode, BursamProductCode::getValues())
             ? BursamProductCode::fromValue($productCode)->description
             : $this->product;
    }

    public function getQuantity(): string
    {
        return $this->quantity;
    }

    public function getUom(): string
    {
        return $this->uom;
    }

    public function getAmount(): float
    {
        return $this->amount;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function getWarehouse(): string
    {
        if ($this->warehouse) {
            return $this->warehouse;
        }

        return '';
    }

    public function getPreviousOwner(): string
    {
        return $this->previous_owner;
    }

    public static function fromArray(array $data): CommodityProductDto
    {
        return new static(
            $data['product'],
            $data['quantity'],
            $data['amount'],
            $data['previous_owner'],
            $data['date_time_of_purchasing_commodity'],
            $data['product_code'] ?? null,
            $data['uom'] ?? '--',
            $data['warehouse'] ?? '',
            $data['warehouse_or_vault_emirates'] ?? '--',
            $data['warehouse_or_vault_country'] ?? '--',
            $data['currency'] ?? 'SAR',
            $data['exchange_rate'] ?? '1',
        );
    }

    public function toArray(): array
    {
        return [
            'product' => $this->getProduct(),
            'quantity' => $this->getQuantity(),
            'uom' => $this->getUom(),
            'amount' => $this->getAmount(),
            'currency' => $this->getCurrency(),
            'warehouse' => $this->getWarehouse(),
            'previous_owner' => $this->getPreviousOwner(),
            'date_time_of_purchasing_commodity' => $this->date_time_of_purchasing_commodity,
        ];
    }
}
