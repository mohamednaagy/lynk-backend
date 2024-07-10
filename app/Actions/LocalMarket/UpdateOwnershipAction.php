<?php

namespace App\Actions\LocalMarket;

use App\Services\LocalMarketService;
use Illuminate\Support\Facades\DB;

class UpdateOwnershipAction
{
    private $localMarketService;

    public function __construct(LocalMarketService $localMarketService)
    {
        $this->localMarketService = $localMarketService;
    }

    public function handle($inventory, $companyId)
    {
        $this->localMarketService->updateOwnership($inventory, $companyId);
        $ownershipData = [];
        $company = DB::table('companies')->where('id', $companyId)->first();

        foreach ($inventory->units as $unit) {
            $previousOwner = DB::table('local_market_unit_ownership')
                ->where('inventory_unit_id', $unit->id)
                ->latest()
                ->first();

            $ownershipData[] = [
                'inventory_unit_id' => $unit->id,
                'owner_type' => $company->type,
                'owner_id' => $companyId,
                'owner_name' => $company->unique_name,
                'previous_owner' => $previousOwner ? $previousOwner->owner_id : $inventory->company_id,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        $chunks = array_chunk($ownershipData, 3000);
        foreach ($chunks as $chunk) {
            DB::table('local_market_unit_ownership')->insert($chunk);
        }
    }
}
