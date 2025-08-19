<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement('DROP VIEW IF EXISTS commodity_type_statistics_view');
        
        DB::statement('
            CREATE VIEW commodity_type_statistics_view AS
            SELECT 
                ct.id AS commodity_type_id,
                
                -- Available Value: Sum of available_quantity * max_price for active items only
                COALESCE(SUM(
                    lmi.available_quantity * ci.max_price
                ), 0) AS available_value,
                
                -- Reserved Value: Sum of reserved_items * max_price for active items only
                COALESCE(SUM(
                    lmi.reserved_items * ci.max_price
                ), 0) AS reserved_value,
                
                -- Total Value: Sum of (available_quantity + reserved_items) * max_price for active items only
                COALESCE(SUM(
                    (lmi.available_quantity + lmi.reserved_items) * ci.max_price
                ), 0) AS total_value

            FROM commodity_types ct
            
            -- Left join with local_market_inventories to get inventory data
            LEFT JOIN local_market_inventories lmi 
                ON ct.id = lmi.commodity_type_id
            
            -- Join with commodity_items to get price information
            LEFT JOIN commodity_items ci 
                ON lmi.commodity_item_id = ci.id 
                AND ci.commodity_type_id = ct.id
            
            -- Join with companies (suppliers) to check if they are active
            LEFT JOIN companies c 
                ON ci.company_id = c.id
            
            -- Join with company_supplier_details to check supplier status
            LEFT JOIN company_supplier_details csd 
                ON c.id = csd.company_id
            
            WHERE ct.deleted_at IS NULL
            AND ct.status = 1
            AND (lmi.id IS NULL OR (
                lmi.deleted_at IS NULL
                AND lmi.status = 1
                AND ci.deleted_at IS NULL
                AND c.deleted_at IS NULL
                AND csd.status = 1
            ))
            
            GROUP BY ct.id
        ');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP VIEW IF EXISTS commodity_type_statistics_view');
        
        // Recreate the original view
        DB::statement('
            CREATE VIEW commodity_type_statistics_view AS
            SELECT 
                lmi.commodity_type_id,

                -- Value Calculations
                COALESCE(SUM(lmi.available_quantity * ci.max_price), 0) AS available_value,
                COALESCE(SUM(lmi.reserved_items * ci.max_price), 0) AS reserved_value,
                COALESCE(SUM((lmi.available_quantity + lmi.reserved_items) * ci.max_price), 0) AS total_value

            FROM local_market_inventories lmi

            JOIN commodity_items ci 
                ON lmi.commodity_item_id = ci.id 
                AND ci.deleted_at IS NULL

            WHERE lmi.deleted_at IS NULL

            GROUP BY lmi.commodity_type_id
        ');
    }
};
