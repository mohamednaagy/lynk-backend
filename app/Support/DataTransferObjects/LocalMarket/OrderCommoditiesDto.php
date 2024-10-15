<?php

namespace App\Support\DataTransferObjects\LocalMarket;

class OrderCommoditiesDto
{
    private $inventories;

    public function __construct($inventories)
    {
        $this->inventories = $inventories;

    }

    public static function fromArray(array $data): self
    {
        return new self(
            $data['inventories']
        );
    }

    public function toArray(): array
    {
        return [
            'inventories' => $this->inventories->toArray(),
        ];
    }

    public function getInventories()
    {
        return $this->inventories;
    }
}
