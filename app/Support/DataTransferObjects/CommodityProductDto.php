<?php

namespace App\Support\DataTransferObjects;

use App\Enums\BursamProductCode;

class CommodityProductDto
{
    public function __construct(
        protected string $product,
        protected string $quantity,
        protected string|float $amount,
        protected array|string $previous_owner,
        protected string $date_time_of_purchasing_commodity,
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
        return in_array($this->product, BursamProductCode::getValues())
            ? BursamProductCode::fromValue($this->product)->description
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

    public function getAmount(): string|float
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

    public function getPreviousOwner(): string|array
    {
        return $this->previous_owner;
    }

    public function getPreviousOwnerAsArray(): array
    {
        return is_array($this->previous_owner)
            ? $this->previous_owner
            : [$this->previous_owner];
    }

    public function getImplodedPreviousOwner($separator = ','): string
    {
        return implode($separator, $this->getPreviousOwnerAsArray());
    }

    public static function fromArray(array $data): CommodityProductDto
    {
        return new static(
            $data['product'],
            $data['quantity'],
            $data['amount'],
            $data['previous_owner'],
            $data['date_time_of_purchasing_commodity'],
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
            'product' => $this->product,
            'quantity' => $this->quantity,
            'uom' => $this->uom,
            'amount' => $this->amount,
            'currency' => $this->currency,
            'warehouse' => $this->warehouse,
            'previous_owner' => $this->previous_owner,
            'date_time_of_purchasing_commodity' => $this->date_time_of_purchasing_commodity,
        ];
    }
}
