<?php

namespace App\Http\Controllers\Api\V1\LocalMarket\Test;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class LoanController extends Controller
{
    /**
     * Public method to calculate loan coverage
     */
    public function calculateLoanCoverage(Request $request)
    {
        try {
            // Start time
            $startTime = microtime(true);
            $maxExecutionTime = ini_get('max_execution_time'); // PHP's max execution time
            $customThreshold = $maxExecutionTime - 2; // Custom threshold to stop execution
            ini_set('max_execution_time', $maxExecutionTime);

            // Start time
            $startTime = microtime(true);

            $loan = $request->input('loan');
            $inventories = $request->input('inventories');

            // Validate inputs
            $validatedData = $request->validate([
                'loan' => 'required|min:1',
                'inventories' => 'required|array|min:1',
                'inventories.*.id' => 'required|integer',
                'inventories.*.price' => 'required|min:1',
                'inventories.*.count' => 'required|integer|min:0',
            ]);

            if (! is_numeric($loan) || intval($loan) != $loan) {
                return response()->json([
                    'status' => 'uncovered',
                    'used_units' => 0,
                    'total_price' => 0,
                    'elapsed_time' => 0,
                    'extra' => [
                        'msg' => 'Loan has a fraction part',
                    ],
                    'inventories' => [],
                ], 422);
            }

            // Step 1: Filter out inventories where price > loan
            $inventories = array_filter($inventories, fn ($item) => $item['count'] > 0 && $item['price'] <= $loan);

            // Step 2: Sort based on divisibility and then by descending price
            usort($inventories, function ($a, $b) {
                return $b['price'] <=> $a['price'];
            });

            $this->logInfo('Loan Amount', ['loan' => $loan]);
            $this->logInfo('Inventories', ['inventories' => $inventories]);

            // Initialize necessary variables
            $loanRemaining = $loan;
            $queue = [];
            $coverage = 0;
            $tempInventories = $inventories;
            $skippedInventories = [];
            $inventoriesMap = [];

            // Run optimization logic
            $success = $this->processInventories($loanRemaining, $tempInventories, $queue, $inventoriesMap, $coverage);

            $loanDeadAmount = [];
            while ((! $success && $loanRemaining > 0 && ! empty($queue)) || count($queue) > 10000) {

                $elapsedTime = microtime(true) - $startTime;
                if ($elapsedTime > $customThreshold) {
                    throw new \Exception('Time out!!', Response::HTTP_REQUEST_TIMEOUT);
                }

                $this->logInfo('Taken inventories map', $inventoriesMap);

                $loanDeadAmount[] = $loanRemaining; // i.e The uncovered amount across all the provided inventories.

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
            $endTime = microtime(true);
            $elapsedTime = round(($endTime - $startTime), 4);

            if ($result['uncovered'] === $loan) {
                $this->logInfo('Loan uncovered');
            }

            $response = [
                'status' => $result['uncovered'] === 0 ? 'covered' : 'uncovered',
                'used_units' => 0,
                'total_price' => 0,
                'elapsed_time' => $elapsedTime,
                'inventories' => [],
            ];

            // Initialize variables to accumulate used_units and total_price
            $totalUsedUnits = 0;
            $totalPriceCovered = 0;

            foreach ($result['usedSuppliers'] as $inventoryIndex => $count) {
                $inventory = $inventories[$inventoryIndex];
                $totalPrice = $inventory['price'] * $count;

                // Update total used units and total price covered
                $totalUsedUnits += $count;
                $totalPriceCovered += $totalPrice;

                // Add inventory details to the response
                $response['inventories'][] = [
                    'inventory_id' => $inventory['id'],
                    'actual_count' => $inventory['count'],
                    'used_count' => $count,
                    'price' => $inventory['price'],
                    'total_price' => $totalPrice,
                ];
            }

            // Set the total used units and total price in the response
            $response['used_units'] = $totalUsedUnits;
            $response['total_price'] = $totalPriceCovered;

            return response()->json($response);

        } catch (\Exception $e) {
            $endTime = microtime(true);
            $elapsedTime = round(($endTime - $startTime), 4);

            return response()->json([
                'status' => 'uncovered',
                'used_units' => 0,
                'total_price' => 0,
                'elapsed_time' => $elapsedTime,
                'extra' => [
                    'msg' => 'Request Timeout!',
                ],
                'inventories' => [],
            ], $e->getCode());
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
        $usedSuppliers = array_count_values($queue);

        return [
            'coverage' => $coverage,
            'uncovered' => max(0, $loanRemaining),
            'usedSuppliers' => $usedSuppliers,
        ];
    }

    /**
     * Helper function to log information
     */
    private function logInfo(string $logInfo, array $data = []): void
    {
        Log::channel('local_market')->info($logInfo, $data);
    }
}
