<?php

namespace App\Http\Controllers\Api\V1\LocalMarket\Test;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class LoanController extends Controller
{
    /**
     * Public method to calculate loan coverage
     */
    public function calculateLoanCoverage(Request $request)
    {
        // Start time
        $startTime = microtime(true);

        $loan = $request->input('loan');
        $inventories = $request->input('inventories');

        // Validate inputs
        $validatedData = $request->validate([
            'loan' => 'required|integer|min:1',
            'inventories' => 'required|array|min:1',
            'inventories.*.id' => 'required|integer',
            'inventories.*.price' => 'required|integer|min:1',
            'inventories.*.count' => 'required|integer|min:0',
        ]);

        // Filter out inventories where price > $loan
        $inventories = array_filter($inventories, function ($item) use ($loan) {
            return $item['price'] <= $loan;
        });

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

        // Run optimization logic
        $success = $this->processInventories($loanRemaining, $tempInventories, $queue, $coverage);
        $loanDeadAmount = [];
        while ((! $success && $loanRemaining > 0 && ! empty($queue)) || count($queue) > 10000) {
            $loanDeadAmount[] = $loanRemaining;
            $usedInventories = array_count_values($queue);
            $usedInventoryIndex = array_key_first($usedInventories);

            if (($key = array_search($usedInventoryIndex, $queue)) !== false) {
                unset($queue[$key]);

                $loanRemaining += $inventories[$usedInventoryIndex]['price'];
                $coverage -= $inventories[$usedInventoryIndex]['price'];

                if (! isset($skippedInventories[$usedInventoryIndex]) && isset($tempInventories[$usedInventoryIndex])) {
                    $skippedInventories[$usedInventoryIndex] = $tempInventories[$usedInventoryIndex];
                }

                $removedInventory = $skippedInventories[$usedInventoryIndex] ?? null;
                unset($tempInventories[$usedInventoryIndex]);

                // Debugging message
                if ($removedInventory) {
                    $this->logInfo("Skipping the inventory ID {$removedInventory['id']}, price {$removedInventory['price']}, available count {$removedInventory['count']}");
                    $this->logInfo("Removed an item from the queue related the inventory ID {$removedInventory['id']}");
                    $this->logInfo("Adjusted loan remaining {$loanRemaining}, adjusted coverage {$coverage}");
                    $currentUsedItemCount = $usedInventories[$usedInventoryIndex] - 1;
                    $this->logInfo("Current used item count in the queue related to the inventory ID {$removedInventory['id']}: {$currentUsedItemCount}");
                }

                $success = $this->processInventories($loanRemaining, $tempInventories, $queue, $coverage, $loanDeadAmount);
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
    }

    /**
     * Process inventories to cover a portion of the loan
     */
    private function processInventories(int &$loanRemaining, array &$inventories, array &$queue, int &$coverage, array $loanDeadAmount = []): bool
    {
        foreach ($inventories as $inventoryIndex => &$inventory) {
            $price = $inventory['price'];
            $count = $inventory['count'];

            $this->logInfo("Processing Inventory ID {$inventory['id']}: price = $price, count = $count");

            if ($count === 0) {
                $this->logInfo("Inventory ID {$inventory['id']} has run out");

                continue;
            }

            $possibleUses = min((int) floor($loanRemaining / $price), $count);
            $this->logInfo("Possible uses = $possibleUses, Loan remaining = $loanRemaining, Loan coverage = $coverage");

            for ($i = 0; $i < $possibleUses; $i++) {
                if ($loanRemaining >= $price) {
                    $queue[] = $inventoryIndex;
                    $loanRemaining -= $price;
                    $coverage += $price;
                    $inventory['count']--;
                    $this->logInfo("Used inventory ID {$inventory['id']}, price = $price, remaining loan = $loanRemaining, loan coverage = $coverage");
                } else {
                    break;
                }
            }

            if ($loanRemaining <= 0) {
                $this->logInfo('Loan fully covered');

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
