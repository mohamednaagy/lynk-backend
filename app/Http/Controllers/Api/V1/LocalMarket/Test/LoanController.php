<?php

namespace App\Http\Controllers\Api\V1\LocalMarket\Test;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class LoanController extends Controller
{
    private const MAX_USED_ITEMS = 10000;

    private const MAX_EXECUTION_TIME = 3;

    /**
     * Public method to calculate loan coverage
     */
    public function calculateLoanCoverage(Request $request): JsonResponse
    {
        $loanDeadAmount = [];

        // Start time
        $startTime = microtime(true);

        $loan = $request->input('loan');
        $inventories = $request->input('inventories');

        // Validate inputs
        $request->validate([
            'loan' => 'required|min:1',
            'inventories' => 'required|array|min:1',
            'inventories.*.id' => 'required|integer',
            'inventories.*.price' => 'required|min:1',
            'inventories.*.count' => 'required|integer|min:0',
        ]);

        $this->logInfo('Loan Amount', ['loan' => $loan]);

        try {
            if (! is_numeric($loan) || intval($loan) != $loan) {
                $elapsedTime = $this->getElapsedTime($startTime);
                $msg = 'Loan has a fraction part';
                $output = $this->output($loan, $inventories, $elapsedTime, $loanDeadAmount, $msg);

                return response()->json($output, 422);
            }

            // Step 1: Filter out inventories where price > loan
            $inventories = array_filter($inventories, fn ($item) => $item['count'] > 0 && $item['price'] <= $loan);

            // Step 2: Sort based on divisibility and then by descending price
            usort($inventories, function ($a, $b) {
                return $b['price'] <=> $a['price'];
            });

            $this->logInfo('Inventories', ['inventories' => $inventories]);

            if (empty($inventories)) {
                $elapsedTime = $this->getElapsedTime($startTime);
                $output = $this->output($loan, $inventories, $elapsedTime, $loanDeadAmount);

                return response()->json($output);
            }

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
            if ($loanRemaining == 0 && $itemUsed > self::MAX_USED_ITEMS) {
                $this->logInfo('Loan covered with ('.number_format($itemUsed).' Item) , so its unacceptable');
            }

            while ((! $success && $loanRemaining > 0 && ! empty($queue)) || count($queue) > self::MAX_USED_ITEMS) {
                $this->logInfo('Taken inventories map', $inventoriesMap);

                if ($loanRemaining != 0 && ! in_array($loanRemaining, $loanDeadAmount)) {
                    $loanDeadAmount[] = $loanRemaining; // i.e The uncovered amount across all the provided inventories.
                }

                $elapsedTime = microtime(true) - $startTime;
                if ($elapsedTime >= self::MAX_EXECUTION_TIME) {
                    throw new \Exception('Request timeout!', Response::HTTP_REQUEST_TIMEOUT);
                }

                $skipInventoryIndex = $inventoriesMap[0];
                $usedInventories = array_count_values($queue);

                if (($key = array_search($skipInventoryIndex, $queue)) !== false) {
                    unset($queue[$key]);

                    $loanRemaining += $inventories[$skipInventoryIndex]['price'];
                    $coverage -= $inventories[$skipInventoryIndex]['price'];

                    if (! isset($skippedInventories[$skipInventoryIndex]) && isset($tempInventories[$skipInventoryIndex])) {
                        $skippedInventories[$skipInventoryIndex] = $tempInventories[$skipInventoryIndex];
                    }

                    $removedInventory = $skippedInventories[$skipInventoryIndex] ?? null;
                    unset($tempInventories[$skipInventoryIndex]);

                    // Debugging message
                    if ($removedInventory) {
                        $this->logInfo("Skipping the inventory ID {$removedInventory['id']}, price {$removedInventory['price']}, available count {$removedInventory['count']}");
                        $this->logInfo("Removed an item from the queue related the inventory ID {$removedInventory['id']}");
                        $this->logInfo("Adjusted loan remaining {$loanRemaining}, adjusted coverage {$coverage}");
                        $currentUsedItemCount = $usedInventories[$skipInventoryIndex] - 1;
                        $this->logInfo("Current used item count in the queue related to the inventory ID {$removedInventory['id']}: {$currentUsedItemCount}");
                    }

                    $success = $this->processInventories($loanRemaining, $tempInventories, $queue, $inventoriesMap, $coverage, $loanDeadAmount);
                } else {
                    $this->logInfo('Remove inventory ID('.$inventories[$skipInventoryIndex]['id'].') from the inventories map');
                    unset($inventoriesMap[0]);
                    $inventoriesMap = array_values($inventoriesMap);
                    $this->logInfo('The current inventories map', $inventoriesMap);
                }
            }

            $result = $this->getLoanCoverageResult($coverage, $loanRemaining, $queue);

            // Prepare result in the desired format
            $elapsedTime = $this->getElapsedTime($startTime);

            $output = $this->output($loan, $inventories, $elapsedTime, $loanDeadAmount, null, $result);

            return response()->json($output);

        } catch (Exception $e) {
            $this->logInfo('Loan uncovered', $loanDeadAmount);
            $this->logInfo($e->getMessage());

            $elapsedTime = $this->getElapsedTime($startTime);

            $output = $this->output($loan, $inventories, $elapsedTime, $loanDeadAmount, $e->getMessage());

            return response()->json($output, $e->getCode());
        }
    }

    /**
     * Process inventories to cover a portion of the loan
     */
    private function processInventories(int &$loanRemaining, array &$inventories, array &$queue, array &$inventoriesMap, int &$coverage, array $loanDeadAmount = []): bool
    {
        foreach ($inventories as $inventoryIndex => &$inventory) {
            $price = $inventory['price'];
            $count = $inventory['count'];

            if ($count === 0) {
                $this->logInfo("Inventory ID {$inventory['id']} is out of stock");

                continue;
            }

            $possibleUses = min((int) floor($loanRemaining / $price), $count);

            // Log only if there is a meaningful action
            if ($possibleUses > 0) {
                $this->logInfo("Processing Inventory ID {$inventory['id']}: price = $price, possible uses = $possibleUses");
            }

            for ($i = 0; $i < $possibleUses; $i++) {
                if ($loanRemaining >= $price) {
                    $queue[] = $inventoryIndex;
                    $loanRemaining -= $price;
                    $coverage += $price;
                    $inventory['count']--;

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
    private function getLoanCoverageResult(int $coverage, int $loanRemaining, array $queue): array
    {
        $usedInventories = array_count_values($queue);

        return [
            'coverage' => $coverage,
            'uncovered' => max(0, $loanRemaining),
            'usedInventories' => $usedInventories,
        ];
    }

    private function loanCoverageFastChecking($loan, $inventories): void
    {
        $totalValue = 0;
        $totalItemsUsed = 0;

        foreach ($inventories as $inventory) {
            if ($totalItemsUsed >= self::MAX_USED_ITEMS) {
                break;
            }

            $itemsToUse = min($inventory['count'], self::MAX_USED_ITEMS - $totalItemsUsed);
            $totalValue += $itemsToUse * $inventory['price'];
            $totalItemsUsed += $itemsToUse;
        }

        if ($totalValue < $loan) {
            throw new Exception('The loan cannot be covered. Accumulated value for the first '.self::MAX_USED_ITEMS." items: $totalValue", 422);
        }
    }

    private function output($loan, array $inventories, $elapsedTime, array $loanDeadAmount, ?string $msg = null, array $coverageResult = []): array
    {
        $coverageResult = empty($coverageResult) ? [
            'uncovered' => $loan,
            'usedInventories' => [],
        ] : $coverageResult;

        $isCovered = $coverageResult['uncovered'] === 0;

        if (! $isCovered && empty($loanDeadAmount)) {
            $loanDeadAmount = [$loan];
        }

        $output = [
            'status' => $isCovered ? 'covered' : 'uncovered',
            'used_units' => 0,
            'total_price' => 0,
            'elapsed_time' => $elapsedTime,
            'extra' => [
                'msg' => $msg,
                'suggested_inventories' => $isCovered ? [] : $loanDeadAmount,
            ],
            'inventories' => [],
        ];

        // Initialize variables to accumulate used_units and total_price
        $totalUsedUnits = 0;
        $totalPriceCovered = 0;

        foreach ($coverageResult['usedInventories'] as $inventoryIndex => $count) {
            $inventory = $inventories[$inventoryIndex];
            $totalPrice = $inventory['price'] * $count;

            // Update total used units and total price covered
            $totalUsedUnits += $count;
            $totalPriceCovered += $totalPrice;

            // Add inventory details to the response
            $output['inventories'][] = [
                'inventory_id' => $inventory['id'],
                'actual_count' => $inventory['count'],
                'used_count' => $count,
                'price' => $inventory['price'],
                'total_price' => $totalPrice,
            ];
        }

        // Set the total used units and total price in the response
        $output['used_units'] = $totalUsedUnits;
        $output['total_price'] = $totalPriceCovered;

        $this->logInfo('The loan is '.$output['status']);

        if (! $isCovered) {
            $this->logInfo('the Suggested inventories', $loanDeadAmount);
        }

        if (! is_null($msg)) {
            $this->logInfo('Extra info: '.$msg);
        }

        return $output;
    }

    private function getElapsedTime($startTime)
    {
        $endTime = microtime(true);

        return round(($endTime - $startTime), 4);
    }

    /**
     * Helper function to log information
     */
    private function logInfo(string $logInfo, array $data = []): void
    {
        Log::channel(LOG_CHANNEL_LOCAL_MARKET)->info($logInfo, $data);
    }
}
