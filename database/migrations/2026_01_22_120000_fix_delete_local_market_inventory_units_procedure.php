<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Fixes the DeleteLocalMarketInventoryUnits stored procedure to remove
     * transaction management (START TRANSACTION/COMMIT/ROLLBACK) so it works
     * correctly when called inside Laravel's DB::transaction().
     */
    public function up(): void
    {
        DB::unprepared('DROP PROCEDURE IF EXISTS DeleteLocalMarketInventoryUnits');

        DB::unprepared('
            CREATE PROCEDURE DeleteLocalMarketInventoryUnits(
                IN p_inventory_id INT,
                IN p_status INT,
                IN p_limit INT
            )
            BEGIN
                DECLARE current_batch INT UNSIGNED DEFAULT 0;
                DECLARE batch_size INT UNSIGNED DEFAULT 100000;
                DECLARE total_units_remaining INT UNSIGNED;
                DECLARE time_now DATETIME;
                DECLARE rows_affected INT DEFAULT 0;

                DECLARE EXIT HANDLER FOR SQLEXCEPTION
                BEGIN
                    -- No ROLLBACK - let outer transaction handle it
                    SIGNAL SQLSTATE \'45000\' SET MESSAGE_TEXT = \'An error occurred in DeleteLocalMarketInventoryUnits.\';
                END;

                SET total_units_remaining = p_limit;
                SET time_now = NOW();

                inventory_units_loop: WHILE total_units_remaining > 0 DO
                    IF total_units_remaining > batch_size THEN
                        SET current_batch = batch_size;
                    ELSE
                        SET current_batch = total_units_remaining;
                    END IF;

                    UPDATE local_market_inventory_units
                    SET deleted_at = time_now
                    WHERE local_market_inventory_id = p_inventory_id
                      AND status = p_status
                      AND deleted_at IS NULL
                    LIMIT current_batch;

                    SET rows_affected = ROW_COUNT();

                    -- Exit loop if no more rows to update
                    IF rows_affected = 0 THEN
                        LEAVE inventory_units_loop;
                    END IF;

                    SET total_units_remaining = total_units_remaining - rows_affected;
                END WHILE inventory_units_loop;
            END
        ');
    }

    /**
     * Reverse the migrations.
     *
     * Restores the original procedure with transaction management.
     * WARNING: This will reintroduce the bug. Only use for rollback if necessary.
     */
    public function down(): void
    {
        DB::unprepared('DROP PROCEDURE IF EXISTS DeleteLocalMarketInventoryUnits');

        DB::unprepared('
            CREATE PROCEDURE DeleteLocalMarketInventoryUnits(
                IN p_inventory_id INT,
                IN p_status INT,
                IN p_limit INT
            )
            BEGIN
                DECLARE current_batch INT UNSIGNED DEFAULT 0;
                DECLARE batch_size INT UNSIGNED DEFAULT 100000;
                DECLARE total_units_remaining INT UNSIGNED;
                DECLARE time_now DATETIME;
                DECLARE EXIT HANDLER FOR SQLEXCEPTION
                BEGIN
                    ROLLBACK;
                    SIGNAL SQLSTATE \'45000\' SET MESSAGE_TEXT = \'An error occurred. Rolling back transaction.\';
                END;

                SET total_units_remaining = p_limit;
                SET time_now = NOW();
                START TRANSACTION;

                WHILE total_units_remaining > 0 DO
                    IF total_units_remaining > batch_size THEN
                        SET current_batch = batch_size;
                    ELSE
                        SET current_batch = total_units_remaining;
                    END IF;

                    UPDATE local_market_inventory_units
                    SET deleted_at = time_now
                    WHERE local_market_inventory_id = p_inventory_id
                      AND status = p_status
                      AND deleted_at IS NULL
                    LIMIT current_batch;

                    SET total_units_remaining = total_units_remaining - current_batch;
                END WHILE;

                COMMIT;
            END
        ');
    }
};
