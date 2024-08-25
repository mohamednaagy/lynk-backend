<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class OwnershipService
{
    public function changeUnitOwnership($units, $ownerType, $ownerIdentifier)
    {
        $timestamp = now()->format('Y-m-d H:i:s');

        // Use array_map for better performance over foreach
        $ownershipData = array_map(function ($unit) use ($ownerType, $ownerIdentifier, $timestamp) {
            return [
                'unit_id' => $unit['id'],
                'current_owner' => $ownerIdentifier,
                'current_owner_type' => $ownerType,
                'previous_owner' => null,
                'previous_owner_type' => null,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ];
        }, $units);

        // Chunk and insert in bulk
        foreach (array_chunk($ownershipData, 3000) as $chunk) {
            DB::table('local_market_unit_ownership')->insert($chunk);
        }
    }
}
