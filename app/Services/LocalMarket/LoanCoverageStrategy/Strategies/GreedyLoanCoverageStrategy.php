<?php

namespace App\Services\LocalMarket\LoanCoverageStrategy\Strategies;

use App\Services\LocalMarket\LoanCoverageStrategy\BaseLoanCoverageStrategy;
use App\Services\LocalMarket\LoanCoverageStrategy\LoanCoverageTranslator;

class GreedyLoanCoverageStrategy extends BaseLoanCoverageStrategy
{
    public function calculateCombination(int $loanAmount, array $inventories): array
    {
        $this->logInfo('Using GreedyLoanCoverageStrategy');

        $finalSelectedInventories = [];
        $maxCoveredAmount = 0;
        $loanCovered = false; // Early exit flag

        $this->logInfo("Trying to cover loan amount of $loanAmount with inventories...");

        // Outer loop to iterate through all inventories
        foreach ($inventories as $outerIndex => $outerInventory) {
            if ($loanCovered) {
                break; // Early exit if already covered
            }

            $this->logInfo(" ====== Starting new combination attempt with inventory {$outerInventory['id']} =======");

            $coveredAmount = 0;
            $usedUnits = 0;
            $selectedInventories = [];

            // Inner loop to try combinations starting with the current outer inventory
            for ($innerIndex = $outerIndex; $innerIndex < count($inventories); $innerIndex++) {
                $inv = $inventories[$innerIndex];
                if ($coveredAmount >= $loanAmount || $usedUnits >= $this->maxUnitsPerTrader) {
                    break; // Stop if the loan is covered or max units reached
                }

                // Calculate available units and max affordable units
                $availableUnits = min($inv['available_quantity'], $this->maxUnitsPerTrader - $usedUnits);
                $unitPrice = $inv['max_price'];
                $remainingLoanAmount = $loanAmount - $coveredAmount;
                $maxAffordableUnits = (int) floor($remainingLoanAmount / $unitPrice);

                // Determine the number of units to use and the amount they cover
                $unitsToUse = min($availableUnits, $maxAffordableUnits);
                $amountToCover = $unitsToUse * $unitPrice;

                $this->logInfo("Attempting inventory {$inv['id']} with {$unitsToUse} units at {$unitPrice} per unit, covering {$amountToCover}");

                if ($amountToCover > 0) {
                    $selectedInventories[] = LoanCoverageTranslator::translate($inv, $unitsToUse, $amountToCover);

                    $coveredAmount += $amountToCover;
                    $usedUnits += $unitsToUse;
                    $this->logInfo("Updated covered amount: $coveredAmount, used units: $usedUnits");
                }

                // Check if we've covered the loan amount
                if ($coveredAmount >= $loanAmount) {
                    // Update max covered amount if this combination is better
                    $maxCoveredAmount = $coveredAmount;
                    $finalSelectedInventories = $selectedInventories;
                    $loanCovered = true; // Mark as covered for early exit
                    $this->logInfo("Successfully covered the loan amount with a total of $coveredAmount.");
                    break; // Exit the inner loop since we've covered the loan
                }
            }
        }

        if (! $loanCovered) {
            $this->logInfo("Failed to cover the exact loan amount. Covered $maxCoveredAmount of $loanAmount.");

            return [];
        }

        $this->logInfo("Successfully covered the loan amount with a total of $maxCoveredAmount.");
        $this->logInfo('Selected Inventories: ', $finalSelectedInventories);

        return $finalSelectedInventories;
    }
}
