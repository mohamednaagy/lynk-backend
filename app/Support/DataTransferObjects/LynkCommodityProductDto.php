<?php

namespace App\Support\DataTransferObjects;

class LynkCommodityProductDto
{
    public function __construct(
        protected string $product,
        protected string $type,
        protected string $quantity,
        protected string $uom,
        protected $amount,
        protected string $location,
        protected string $currency,
        protected string $original_supplier,
        protected string $previous_owner,
    ) {
    }

    public function getProduct(): string
    {
        return $this->product;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getQuantity(): string
    {
        return $this->quantity;
    }

    public function getUnitOfMeasurement(): string
    {
        return $this->uom;
    }

    public function getAmount(): string
    {
        return $this->amount;
    }

    public function getLocation(): string
    {
        return $this->location;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function getOriginalSupplier(): string
    {
        return $this->original_supplier;
    }

    public function getPreviousOwner(): string
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

    public static function fromArray(array $data): LynkCommodityProductDto
    {
        return new static(
            $data['product'],
            $data['type'],
            $data['quantity'],
            $data['uom'] ?? '--',
            $data['amount'],
            $data['location'] ?? '',
            $data['currency'] ?? 'SAR',
            $data['original_supplier'],
            $data['previous_owner'],
        );
    }

    public function toArray(): array
    {
        return [
            'product' => $this->product,
            'type' => $this->type,
            'quantity' => $this->quantity,
            'uom' => $this->uom,
            'amount' => $this->amount,
            'location' => $this->location,
            'currency' => $this->currency,
            'original_supplier' => $this->original_supplier,
            'previous_owner' => $this->previous_owner,
        ];
    }
}
