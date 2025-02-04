<?php

namespace App\Services\LocalMarket\LoanCoverageStrategy\Strategies;

use App\Models\LocalMarketInventory;
use App\Services\LocalMarket\LoanCoverageStrategy\BaseLoanCoverageStrategy;
use App\Services\LocalMarket\LoanCoverageStrategy\LoanCoverageTranslator;
use Exception;

class OptimizedLoanCoverageStrategy extends BaseLoanCoverageStrategy
{
    /**
     * Public method to calculate loan coverage
     */
    public function calculateCombination(int $loanAmount, array $inventories): array
    {
        $this->logInfo('Using OptimizedLoanCoverageStrategy');
        $this->logInfo('Loan Amount', ['loan' => $loanAmount]);
        $this->logInventories($inventories);

        $coverageGaps = []; // Suggestions for adding required inventories
        $startTime = microtime(true); // Start time

        try {
            // Check the sum and compare it with the loan amount
            $this->loanCoverageFastChecking($loanAmount, $inventories);

            // Initialize necessary variables
            $loanAmountRemaining = $loanAmount;
            $queue = [];
            $coverage = 0;
            $tempInventories = $inventories;
            $skippedInventories = [];
            $inventoriesMap = [];

            // Run optimization logic
            $success = $this->processInventories($loanAmountRemaining, $tempInventories, $queue, $inventoriesMap, $coverage);

            $itemUsed = count($queue);
            if ($loanAmountRemaining == 0 && $itemUsed > $this->maxUnitsPerTrader) {
                $this->logInfo('Loan covered with ('.number_format($itemUsed).' Item) , so its unacceptable');
            }

            while ((! $success && $loanAmountRemaining > 0 && ! empty($queue)) || count($queue) > $this->maxUnitsPerTrader) {
                $this->logInfo('Taken inventories map', $inventoriesMap);

                if ($loanAmountRemaining != 0 && ! in_array($loanAmountRemaining, $coverageGaps)) {
                    $coverageGaps[] = $loanAmountRemaining; // i.e The uncovered amount across all the provided inventories.
                }

                $elapsedTime = microtime(true) - $startTime;
                if ($elapsedTime >= $this->loanCoverageTimeout) {
                    throw new Exception('Request timeout!', 408);
                }

                $skipInventoryIndex = $inventoriesMap[0];
                $usedInventories = array_count_values($queue);

                if (($key = array_search($skipInventoryIndex, $queue)) !== false) {
                    unset($queue[$key]);

                    $loanAmountRemaining += $inventories[$skipInventoryIndex]->max_price;
                    $coverage -= $inventories[$skipInventoryIndex]->max_price;

                    if (! isset($skippedInventories[$skipInventoryIndex]) && isset($tempInventories[$skipInventoryIndex])) {
                        $skippedInventories[$skipInventoryIndex] = $tempInventories[$skipInventoryIndex];
                    }

                    $removedInventory = $skippedInventories[$skipInventoryIndex] ?? null;
                    unset($tempInventories[$skipInventoryIndex]);

                    // Debugging message
                    if ($removedInventory) {
                        $this->logInfo("Skipping the inventory ID {$removedInventory->id}, max_price {$removedInventory->max_price}, available quantity {$removedInventory->available_quantity}");
                        $this->logInfo("Removed an item from the queue related to the inventory ID {$removedInventory->id}");
                        $this->logInfo("Adjusted loan remaining {$loanAmountRemaining}, adjusted coverage {$coverage}");
                        $currentUsedItemCount = $usedInventories[$skipInventoryIndex] - 1;
                        $this->logInfo("Current used item count in the queue related to the inventory ID {$removedInventory->id}: {$currentUsedItemCount}");
                    }

                    $success = $this->processInventories($loanAmountRemaining, $tempInventories, $queue, $inventoriesMap, $coverage, $coverageGaps);
                } else {
                    $this->logInfo('Remove inventory ID('.$inventories[$skipInventoryIndex]->id.') from the inventories map');
                    unset($inventoriesMap[0]);
                    $inventoriesMap = array_values($inventoriesMap);
                    $this->logInfo('The current inventories map', $inventoriesMap);
                }
            }

            $selectedInventories = $this->getLoanCoverageResult($inventories, $coverage, $loanAmountRemaining, $queue);
            $this->logInfo('Selected Inventories: ', $selectedInventories);

            return $selectedInventories;

        } catch (Exception $e) {
            $elapsedTime = $this->getElapsedTime($startTime);
            $this->logError($loanAmount, $elapsedTime, $coverageGaps, $e->getMessage());

            return [];
        }
    }

    /**
     * Process inventories to cover a portion of the loan
     */
    private function processInventories(int &$loanAmountRemaining, array &$inventories, array &$queue, array &$inventoriesMap, int &$coverage, array $coverageGaps = []): bool
    {
        foreach ($inventories as $inventoryIndex => &$inventory) {
            $price = $inventory->max_price;
            $count = $inventory->available_quantity;

            if ($count === 0) {
                $this->logInfo("Inventory ID {$inventory->id} is out of stock");

                continue;
            }

            $possibleUses = min((int) floor($loanAmountRemaining / $price), $count);

            // Log only if there is a meaningful action
            if ($possibleUses > 0) {
                $this->logInfo("Processing Inventory ID {$inventory->id}: price = $price, possible uses = $possibleUses");
            }

            for ($i = 0; $i < $possibleUses; $i++) {
                if ($loanAmountRemaining >= $price) {
                    $queue[] = $inventoryIndex;
                    $loanAmountRemaining -= $price;
                    $coverage += $price;
                    $inventory->available_quantity--;

                    if (! in_array($inventoryIndex, $inventoriesMap)) {
                        $inventoriesMap[] = $inventoryIndex;
                    }
                } else {
                    break;
                }
            }

            if ($loanAmountRemaining <= 0) {
                $this->logInfo('Loan fully covered with current inventories.');

                return true;
            }
        }

        return false;
    }

    /**
     * Helper function to get loan coverage result
     */
    private function getLoanCoverageResult(array $inventories, int $coverage, int $loanAmountRemaining, array $queue): array
    {
        // Log the input values
        $this->logInfo('getLoanCoverageResult called with:', [
            'coverage' => $coverage,
            'loanRemaining' => $loanAmountRemaining,
            'queue' => $queue,
        ]);

        // Count the used inventories
        $usedInventories = array_count_values($queue);

        $result = [];
        foreach ($usedInventories as $inventoryIndex => $count) {
            // Assuming the queue contains instances of LocalMarketInventory
            $inventory = $inventories[$inventoryIndex];

            // Calculate the amount to cover
            $amountToCover = $count * $inventory->max_price;

            // Use the LoanCoverageTranslator to translate the inventory data
            $translated = LoanCoverageTranslator::translate($inventory, $count, $amountToCover);

            // Add the translated result to the final result array
            $result[] = $translated;
        }

        // Log the final result
        $this->logInfo('Final Loan Coverage Result:', $result);

        return $result;
    }

    private function loanCoverageFastChecking(int $loanAmount, array $inventories): void
    {
        $totalValue = 0;
        $totalItemsUsed = 0;

        foreach ($inventories as $inventory) {
            if ($totalItemsUsed >= $this->maxUnitsPerTrader) {
                break;
            }

            $itemsToUse = min($inventory->available_quantity, $this->maxUnitsPerTrader - $totalItemsUsed);
            $totalValue += $itemsToUse * $inventory->max_price;
            $totalItemsUsed += $itemsToUse;
        }

        if ($totalValue < $loanAmount) {
            throw new Exception('The loan cannot be covered. Accumulated value for the first '.$this->maxUnitsPerTrader." items: $totalValue", 422);
        }
    }

    private function logError(int $loanAmount, string $elapsedTime, array $coverageGaps, ?string $msg = null): void
    {
        $this->logInfo('Exception!', [
            'loan' => $loanAmount,
            'status' => 'uncovered',
            'elapsed_times' => $elapsedTime,
            'error_msg' => $msg,
            'suggested_inventories' => $coverageGaps,
        ]);
    }

    private function logInventories(array $inventories): void
    {
        $this->logInfo('Inventories', ['inventories' => array_map(fn ($inventory) => [
            'id' => $inventory->id,
            'commodity_item_id' => $inventory->commodity_item_id,
            'supplier_location_id' => $inventory->supplier_location_id,
            'reserved_items' => $inventory->reserved_items,
            'available_quantity' => $inventory->available_quantity,
            'status' => (string) $inventory->status,
            'max_price' => $inventory->max_price,
            'commodity_type_id' => $inventory->commodity_type_id,
        ], $inventories)]);
    }
}
