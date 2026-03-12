<?php

namespace App\Services\LocalMarket\LoanCoverage;

use App\Models\LocalMarketInventory;
use Exception;
use Illuminate\Support\Facades\Log;

class LoanCoverage
{
    private int $maxUnitsPerTrader;

    private float $loanCoverageTimeout;

    public function __construct()
    {
        $this->maxUnitsPerTrader = config('trader.providers.lynk.max_units_per_trader', 10000);
        $this->loanCoverageTimeout = config('trader.providers.lynk.loan_coverage_timeout', 3);
    }

    public function calculateCombination(int $loanAmount, array $inventories): array
    {
        $this->logInfo('Loan coverage calculation started', [
            'loan' => $loanAmount,
            'inventories_count' => count($inventories),
        ]);

        $coverageGaps = [];
        $startTime = microtime(true);

        try {
            $this->loanCoverageFastChecking($loanAmount, $inventories);

            $loanAmountRemaining = $loanAmount;
            $queue = [];
            $coverage = 0;
            $tempInventories = $inventories;
            $skippedInventories = [];
            $inventoriesMap = [];

            $success = $this->processInventories($loanAmountRemaining, $tempInventories, $queue, $inventoriesMap, $coverage);

            $itemUsed = count($queue);
            if ($loanAmountRemaining == 0 && $itemUsed > $this->maxUnitsPerTrader) {
                $this->logInfo('Loan covered with ('.number_format($itemUsed).' Item) , so its unacceptable');
            }

            while ((! $success && $loanAmountRemaining > 0 && ! empty($queue)) || count($queue) > $this->maxUnitsPerTrader) {
                if ($loanAmountRemaining != 0 && ! in_array($loanAmountRemaining, $coverageGaps)) {
                    $coverageGaps[] = $loanAmountRemaining;
                }

                $elapsedTime = microtime(true) - $startTime;
                if ($elapsedTime >= $this->loanCoverageTimeout) {
                    throw new Exception('Request timeout!', 408);
                }

                $skipInventoryIndex = $inventoriesMap[0];
                $usedInventories = array_count_values($queue);

                if (($key = array_search($skipInventoryIndex, $queue)) !== false) {
                    unset($queue[$key]);

                    /** @var LocalMarketInventory $inventory */
                    $inventory = $inventories[$skipInventoryIndex];

                    $loanAmountRemaining += $inventory->max_price;
                    $coverage -= $inventory->max_price;

                    if (! isset($skippedInventories[$skipInventoryIndex]) && isset($tempInventories[$skipInventoryIndex])) {
                        $skippedInventories[$skipInventoryIndex] = $tempInventories[$skipInventoryIndex];
                    }

                    $removedInventory = $skippedInventories[$skipInventoryIndex] ?? null;
                    unset($tempInventories[$skipInventoryIndex]);

                    $success = $this->processInventories($loanAmountRemaining, $tempInventories, $queue, $inventoriesMap, $coverage, $coverageGaps);
                } else {
                    /** @var LocalMarketInventory $inventory */
                    $inventory = $inventories[$skipInventoryIndex];

                    unset($inventoriesMap[0]);
                    $inventoriesMap = array_values($inventoriesMap);
                }
            }

            $selectedInventories = $this->getLoanCoverageResult($inventories, $coverage, $loanAmountRemaining, $queue);
            $this->logInfo('Loan coverage calculation finished', [
                'loan' => $loanAmount,
                'covered_amount' => $coverage,
                'remaining_amount' => $loanAmountRemaining,
                'used_inventories_count' => count($selectedInventories),
            ]);

            return $selectedInventories;
        } catch (Exception $e) {
            $elapsedTime = $this->getElapsedTime($startTime);
            $this->logError($loanAmount, $elapsedTime, $coverageGaps, $e->getMessage());

            return [];
        }
    }

    private function processInventories(int &$loanAmountRemaining, array &$inventories, array &$queue, array &$inventoriesMap, int &$coverage, array $coverageGaps = []): bool
    {
        foreach ($inventories as $inventoryIndex => &$inventory) {
            $price = $inventory->max_price;
            $count = $inventory->available_quantity;

            if ($count === 0) {
                continue;
            }

            $possibleUses = min((int) floor($loanAmountRemaining / $price), $count);

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
                return true;
            }
        }

        return false;
    }

    private function getLoanCoverageResult(array $inventories, int $coverage, int $loanAmountRemaining, array $queue): array
    {
        $usedInventories = array_count_values($queue);

        $result = [];
        foreach ($usedInventories as $inventoryIndex => $count) {
            /** @var LocalMarketInventory $inventory */
            $inventory = $inventories[$inventoryIndex];

            $amountToCover = $count * $inventory->max_price;

            $translated = LoanCoverageTranslator::translate($inventory, $count, $amountToCover);

            $result[] = $translated;
        }

        return $result;
    }

    private function loanCoverageFastChecking(int $loanAmount, array $inventories): void
    {
        $totalValue = 0;
        $totalItemsUsed = 0;

        foreach ($inventories as $inventory) {
            /** @var LocalMarketInventory $inventory */
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
        $this->logInfo('Inventories', ['inventories' => array_map(fn (LocalMarketInventory $inventory) => [
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

    private function logInfo(string $message, array $data = []): void
    {
        Log::channel(LOG_CHANNEL_LOCAL_MARKET)->info($message, $data);
    }

    private function getElapsedTime($startTime): float
    {
        $endTime = microtime(true);

        return round(($endTime - $startTime), 4);
    }
}
