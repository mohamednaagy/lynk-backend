<?php

namespace App\Services\LocalMarket\LoanCoverageStrategy\Strategies;

use App\Models\LoanCoverageHistory;
use App\Models\LocalMarketInventory;
use App\Models\LocalMarketOrder;
use App\Services\LocalMarket\LoanCoverageStrategy\BaseLoanCoverageStrategy;
use App\Services\LocalMarket\LoanCoverageStrategy\LoanCoverageTranslator;
use Exception;

class OptimizedLoanCoverageStrategy extends BaseLoanCoverageStrategy
{
    /**
     * Public method to calculate loan coverage
     */
    public function calculateCombination(LocalMarketOrder $localMarketOrder, array $inventories): array
    {
        $this->logInfo('Using OptimizedLoanCoverageStrategy');

        $loan = $localMarketOrder->amount;
        $coverageGaps = []; // Suggestions for adding required inventories

        // Start time
        $startTime = microtime(true);

        $this->logInfo('Loan Amount', ['loan' => $loan]);
        $this->logInventories($inventories);

        try {
            // Check the sum and compare it with the loan amount
            $this->loanCoverageFastChecking($loan, $inventories);

            // Initialize necessary variables
            $loanRemaining = $loan;
            $queue = [];
            $coverage = 0;
            $tempInventories = $inventories;
            $skippedInventories = [];
            $inventoriesMap = [];

            // Run optimization logic
            $success = $this->processInventories($loanRemaining, $tempInventories, $queue, $inventoriesMap, $coverage);

            $itemUsed = count($queue);
            if ($loanRemaining == 0 && $itemUsed > $this->maxUnitsPerTrader) {
                $this->logInfo('Loan covered with ('.number_format($itemUsed).' Item) , so its unacceptable');
            }

            while ((! $success && $loanRemaining > 0 && ! empty($queue)) || count($queue) > $this->maxUnitsPerTrader) {
                $this->logInfo('Taken inventories map', $inventoriesMap);

                if ($loanRemaining != 0 && ! in_array($loanRemaining, $coverageGaps)) {
                    $coverageGaps[] = $loanRemaining; // i.e The uncovered amount across all the provided inventories.
                }

                $elapsedTime = microtime(true) - $startTime;
                if ($elapsedTime >= $this->loanCoverageTimeout) {
                    throw new Exception('Request timeout!', 408);
                }

                $skipInventoryIndex = $inventoriesMap[0];
                $usedInventories = array_count_values($queue);

                if (($key = array_search($skipInventoryIndex, $queue)) !== false) {
                    unset($queue[$key]);

                    $loanRemaining += $inventories[$skipInventoryIndex]->max_price;
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
                        $this->logInfo("Adjusted loan remaining {$loanRemaining}, adjusted coverage {$coverage}");
                        $currentUsedItemCount = $usedInventories[$skipInventoryIndex] - 1;
                        $this->logInfo("Current used item count in the queue related to the inventory ID {$removedInventory->id}: {$currentUsedItemCount}");
                    }

                    $success = $this->processInventories($loanRemaining, $tempInventories, $queue, $inventoriesMap, $coverage, $coverageGaps);
                } else {
                    $this->logInfo('Remove inventory ID('.$inventories[$skipInventoryIndex]->id.') from the inventories map');
                    unset($inventoriesMap[0]);
                    $inventoriesMap = array_values($inventoriesMap);
                    $this->logInfo('The current inventories map', $inventoriesMap);
                }
            }

            $selectedInventories = $this->getLoanCoverageResult($inventories, $coverage, $loanRemaining, $queue);

            $elapsedTime = $this->getElapsedTime($startTime);
            $this->logInfo('Selected Inventories: ', $selectedInventories);
            LoanCoverageHistory::log($localMarketOrder, 'covered', $elapsedTime, $selectedInventories, $coverageGaps, self::OPTIMIZED_STRATEGY);

            return $selectedInventories;

        } catch (Exception $e) {
            $elapsedTime = $this->getElapsedTime($startTime);
            $this->logError($localMarketOrder, $elapsedTime, $coverageGaps, $e->getMessage());

            return [];
        }
    }

    /**
     * Process inventories to cover a portion of the loan
     */
    private function processInventories(int &$loanRemaining, array &$inventories, array &$queue, array &$inventoriesMap, int &$coverage, array $coverageGaps = []): bool
    {
        foreach ($inventories as $inventoryIndex => &$inventory) {
            $price = $inventory->max_price;
            $count = $inventory->available_quantity;

            if ($count === 0) {
                $this->logInfo("Inventory ID {$inventory->id} is out of stock");

                continue;
            }

            $possibleUses = min((int) floor($loanRemaining / $price), $count);

            // Log only if there is a meaningful action
            if ($possibleUses > 0) {
                $this->logInfo("Processing Inventory ID {$inventory->id}: price = $price, possible uses = $possibleUses");
            }

            for ($i = 0; $i < $possibleUses; $i++) {
                if ($loanRemaining >= $price) {
                    $queue[] = $inventoryIndex;
                    $loanRemaining -= $price;
                    $coverage += $price;
                    $inventory->available_quantity--;

                    if (! in_array($inventoryIndex, $inventoriesMap)) {
                        $inventoriesMap[] = $inventoryIndex;
                    }
                } else {
                    break;
                }
            }

            if ($loanRemaining <= 0) {
                $this->logInfo('Loan fully covered with current inventories.');

                return true;
            }
        }

        return false;
    }

    /**
     * Helper function to get loan coverage result
     */
    private function getLoanCoverageResult(array $inventories, int $coverage, int $loanRemaining, array $queue): array
    {
        // Log the input values
        $this->logInfo('getLoanCoverageResult called with:', [
            'coverage' => $coverage,
            'loanRemaining' => $loanRemaining,
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

    private function loanCoverageFastChecking($loan, $inventories): void
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

        if ($totalValue < $loan) {
            throw new Exception('The loan cannot be covered. Accumulated value for the first '.$this->maxUnitsPerTrader." items: $totalValue", 422);
        }
    }

    private function logError(LocalMarketOrder $localMarketOrder, string $elapsedTime, array $coverageGaps, ?string $msg = null): void
    {
        $this->logInfo('Exception!', [
            'local_market_order_id' => $localMarketOrder->id,
            'loan' => $localMarketOrder->amount,
            'status' => 'uncovered',
            'elapsed_times' => $elapsedTime,
            'error_msg' => $msg,
            'suggested_inventories' => $coverageGaps,
        ]);

        LoanCoverageHistory::log($localMarketOrder, 'uncovered', $elapsedTime, [], $coverageGaps, self::OPTIMIZED_STRATEGY, $msg);
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
