<?php

namespace App\Support\DataTransferObjects\LocalMarket;

class OrderCommoditiesDto
{
    private array $inventoriesId;

    private $inventories;

    private bool $isLoanCovered;

    private float $remainingLoan;

    public function __construct(array $inventoriesId, $inventories, bool $isLoanCovered, float $remainingLoan)
    {
        $this->inventoriesId = $inventoriesId;
        $this->inventories = $inventories;
        $this->isLoanCovered = $isLoanCovered;
        $this->remainingLoan = $remainingLoan;
    }

    public static function fromArray(array $data): self
    {
        return new self(
            $data['inventories_id'],
            $data['inventories'],
            $data['isLoanCovered'],
            $data['remainingLoan']
        );
    }

    public function toArray(): array
    {
        return [
            'inventories_id' => $this->inventoriesId,
            'inventories' => $this->inventories->toArray(),
            'isLoanCovered' => $this->isLoanCovered,
            'remainingLoan' => $this->remainingLoan,
        ];
    }

    public function getInventories()
    {
        return $this->inventories;
    }

    public function getInventoriesIds()
    {
        return $this->inventoriesId;
    }

    public function getInventoryUnits(): array
    {
        return $this->inventories->availableUnits;
    }

    public function getAllUnits(): array
    {
        $units = [];

        foreach ($this->getInventories() as $inventory) {
            $units = array_merge($units, $inventory['availableUnits']);
        }

        return $units;
    }

    public function isLoanCovered(): bool
    {
        return $this->isLoanCovered;
    }

    public function getRemainingLoan(): float
    {
        return $this->remainingLoan;
    }
}
