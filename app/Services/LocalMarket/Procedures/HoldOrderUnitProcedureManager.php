<?php

namespace App\Services\LocalMarket\Procedures;

use App\Enums\LocalMarket\InventoryUnitsStatus;
use App\Settings\Classes\LocalMurabahaSettings;
use Illuminate\Support\Facades\DB;

class HoldOrderUnitProcedureManager
{
    public function build(): void
    {
        $rotationDepth = app(LocalMurabahaSettings::class)->default_trade_order_rotation_count;

        if ($rotationDepth < 0) {
            throw new \InvalidArgumentException("Invalid rotation depth: $rotationDepth");
        }

        $companyConditions = [];

        for ($i = 0; $i < $rotationDepth; $i++) {
            $companyConditions[] = "(previous_company_id_owner_{$i} != p_company_id OR previous_company_id_owner_{$i} IS NULL)";
        }

        $ownershipCheckClause = implode(' AND ', $companyConditions);
        $reservedStatus = InventoryUnitsStatus::Reserved;

        $procedureSql = <<<SQL
        DROP PROCEDURE IF EXISTS hold_order_unit;

        CREATE PROCEDURE hold_order_unit(
            IN p_order_id INT,
            IN p_number_of_units INT,
            IN p_company_id INT,
            IN p_inventory_id INT
        )
        BEGIN
            UPDATE local_market_inventory_units
            SET
                hold_for = p_order_id,
                status = {$reservedStatus},
                last_purchasing_order_id = p_order_id
            WHERE local_market_inventory_id = p_inventory_id
                AND status = 1
                AND hold_for = 0
                AND deleted_at IS NULL
                AND {$ownershipCheckClause}
            LIMIT p_number_of_units;
        END;
        SQL;

        DB::unprepared($procedureSql);
    }
}
