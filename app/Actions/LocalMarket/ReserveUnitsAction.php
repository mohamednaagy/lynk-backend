<?php

namespace App\Actions\LocalMarket;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class ReserveUnitsAction
{
    private $usedUnits = array();
    private $usedInventories = array();

    public function execute($financial_order, $companyId, $preferredTypes, $loanAmount, $rotations, array $usedInventories = [], array $usedUnitsIDs = [])
    {
        $inventory = app(GetInventoryAction::class)->execute($preferredTypes, $loanAmount, $usedInventories);

        if (empty($inventory)) {
            return false;
        } else {
            Log::info("inventories_nasr" . $inventory->id);
            $suitableUnits = app(GetSuitableUnitsAction::class)->execute($companyId, $inventory, $loanAmount, $usedUnitsIDs, $rotations);
            $this->usedUnits[] = $suitableUnits['availableUnits']; 
            $this->usedInventories[] = $inventory;
            $usedUnitsIDs[] = $this->generateUnitsIDsAction($suitableUnits['availableUnits']);

            if (empty($suitableUnits['remainingLoan'])) {
                return app(BulkInsertUnitsOwnershipAction::class)->execute($financial_order, $preferredTypes, $companyId, $this->usedUnits, $this->usedInventories);
            } else {
                Log::info("remaining" . $suitableUnits['remainingLoan']);
                $usedInventories[] = $inventory->id;
                return $this->execute($financial_order, $companyId, $preferredTypes, $suitableUnits['remainingLoan'], $rotations, $usedInventories, $usedUnitsIDs);
            }
        }
    }

    public function generateUnitsIDsAction($usedUnits)
    {
        return collect($usedUnits)->pluck('id')->toArray();
    }
}
