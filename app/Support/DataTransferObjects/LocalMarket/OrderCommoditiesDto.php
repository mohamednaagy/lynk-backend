<?php

namespace App\Support\DataTransferObjects\LocalMarket;

class OrderCommoditiesDto
{
    private $inventories;

    private float $remainingLoan;

    private int $numberOfSuitableUnits;

    public function __construct($inventories, float $remainingLoan, int $numberOfSuitableUnits)
    {
        $this->inventories = $inventories;
        $this->remainingLoan = $remainingLoan;
        $this->numberOfSuitableUnits = $numberOfSuitableUnits;

    }

    public static function fromArray(array $data): self
    {
        return new self(
            $data['inventories'],
            $data['remainingLoan'],
            $data['numberOfSuitableUnits']
        );
    }

    public function toArray(): array
    {
        return [
            'inventories' => $this->inventories->toArray(),
            'remainingLoan' => $this->remainingLoan,
            'numberOfSuitableUnits' => $this->numberOfSuitableUnits,
        ];
    }

    public function getInventories()
    {
        return $this->inventories;
    }

    public function getInventoriesIds()
    {
        return array_column($this->inventories, 'inventoryId');
    }

    public function getNumberOfSuitableUnits(): int
    {
        return $this->numberOfSuitableUnits;
    }

    public function getRemainingLoan(): float
    {
        return $this->remainingLoan;
    }
}
