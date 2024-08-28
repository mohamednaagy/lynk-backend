<?php

namespace App\Support\DataTransferObjects\LocalMarket;

class OrderCommoditiesDto
{
    private $inventories;

    private bool $isLoanCovered;

    private float $remainingLoan;

    private int $numberOfSuitableUnits;

    public function __construct($inventories, bool $isLoanCovered, float $remainingLoan, int $numberOfSuitableUnits)
    {
        $this->inventories = $inventories;
        $this->isLoanCovered = $isLoanCovered;
        $this->remainingLoan = $remainingLoan;
        $this->numberOfSuitableUnits = $numberOfSuitableUnits;

    }

    public static function fromArray(array $data): self
    {
        return new self(
            $data['inventories'],
            $data['isLoanCovered'],
            $data['remainingLoan'],
            $data['numberOfSuitableUnits']
        );
    }

    public function toArray(): array
    {
        return [
            'inventories' => $this->inventories->toArray(),
            'isLoanCovered' => $this->isLoanCovered,
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

    public function isLoanCovered(): bool
    {
        return $this->isLoanCovered;
    }

    public function getRemainingLoan(): float
    {
        return $this->remainingLoan;
    }
}
