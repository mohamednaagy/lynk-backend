<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class CreateSoftDeleteLocalMarketInventoryUnitsProcedure extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // TODO adel need double check time zone
        DB::unprepared('
        CREATE PROCEDURE DeleteLocalMarketInventoryUnits(
            IN p_inventory_id INT,
            IN p_status INT,
            IN p_limit INT
        )
        BEGIN
                DECLARE current_batch INT UNSIGNED DEFAULT 0;
                DECLARE batch_size INT UNSIGNED DEFAULT 100000; -- Adjust batch size based on your system capacity
                DECLARE total_units_remaining INT UNSIGNED;
                DECLARE time_now DATETIME;
                
                SET total_units_remaining = p_limit;
                SET time_now = CONVERT_TZ(NOW(), @@session.time_zone, \'+03:00\');
            
                -- Continue updating in batches as long as rows are being affected
                WHILE total_units_remaining > 0 DO                
                    -- Determine the size of the current batch
                    IF total_units_remaining > batch_size THEN
                        SET current_batch = batch_size;
                    ELSE
                        SET current_batch = total_units_remaining;
                    END IF;
                    
                    -- Update in chunks
                    UPDATE local_market_inventory_units
                    SET deleted_at = time_now
                    WHERE local_market_inventory_id = p_inventory_id
                    AND status = p_status
                    AND deleted_at IS NULL
                    LIMIT current_batch;
                    
                    SET total_units_remaining = total_units_remaining - current_batch;
                END WHILE;

            END;
        ');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::unprepared('DROP PROCEDURE IF EXISTS DeleteLocalMarketInventoryUnits');
    }
}
