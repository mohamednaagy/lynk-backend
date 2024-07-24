<?php

namespace App\Support\DataTransferObjects;

// TODO_LOCAL_MARKET refactor this class to make a base model with two child classes for lynk and bursam
// double check create a new trader order and show order details
class LynkCommodityProductDto extends CommodityProductDto
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
        protected array|string $previous_owner,
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

    public function getLocation(): string
    {
        return $this->location;
    }

    public function getOriginalSupplier(): string
    {
        return $this->original_supplier;
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
