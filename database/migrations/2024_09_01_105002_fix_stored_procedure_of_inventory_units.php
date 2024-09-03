<?php

use App\Enums\LocalMarket\OwnershipTypes;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        \Illuminate\Support\Facades\DB::unprepared('DROP PROCEDURE IF EXISTS `GenerateRandomQRCodesOptimized`;');
        \Illuminate\Support\Facades\DB::unprepared('DROP PROCEDURE IF EXISTS `GenerateRandomInventoryUnitsQRCode`;');
        \Illuminate\Support\Facades\DB::unprepared('
            CREATE DEFINER=`root`@`localhost` PROCEDURE `GenerateRandomInventoryUnitsQRCode`(
                IN `p_local_market_inventory_id` BIGINT UNSIGNED,
                IN `p_commodity_item_id` BIGINT UNSIGNED,
                IN `p_number_of_units` INT UNSIGNED,
                IN `p_current_owner` BIGINT UNSIGNED
            )
            BEGIN
                DECLARE time_now DATETIME;
                DECLARE current_batch INT UNSIGNED DEFAULT 0;
                DECLARE batch_size INT UNSIGNED DEFAULT 100000; -- Adjust batch size based on your system capacity
                DECLARE total_units_remaining INT UNSIGNED;

                -- Ensure we don\'t exceed two million units
                IF p_number_of_units > 2000000 THEN
                    SET p_number_of_units = 2000000;
                END IF;

                SET time_now = NOW();
                SET total_units_remaining = p_number_of_units;

                -- Disable foreign key checks and unique checks for performance
                SET FOREIGN_KEY_CHECKS = 0;
                SET UNIQUE_CHECKS = 0;
                SET SQL_MODE = \'\';

                -- Create a temporary table to hold the data
                CREATE TEMPORARY TABLE temp_units (
                    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    qr_code VARCHAR(16)
                ) ENGINE=InnoDB;

                -- Process data in batches
                WHILE total_units_remaining > 0 DO
                    IF total_units_remaining > batch_size THEN
                        SET current_batch = batch_size;
                    ELSE
                        SET current_batch = total_units_remaining;
                    END IF;

                    -- Insert a batch of data into the temporary table
                    INSERT INTO temp_units (qr_code)
                    SELECT CONCAT(\'asd\', LPAD(@row := @row + 1, 12, \'0\'))
                    FROM (
                        SELECT @row := 0
                    ) r, (
                        SELECT 0 UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4
                        UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9
                    ) t1,
                    (
                        SELECT 0 UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4
                        UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9
                    ) t2,
                    (
                        SELECT 0 UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4
                        UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9
                    ) t3
                    LIMIT current_batch;

                    -- Insert from the temporary table to the main table in bulk
                    INSERT INTO local_market_inventory_units (
                        local_market_inventory_id,
                        commodity_item_id,
                        qr_code,
                        status,
                        current_owner,
                        current_owner_type,
                        created_at,
                        updated_at
                    )
                    SELECT
                        p_local_market_inventory_id,
                        p_commodity_item_id,
                        qr_code,
                        0,
                        p_current_owner,
                        '.OwnershipTypes::OriginalSupplier.',
                        time_now,
                        time_now
                    FROM temp_units;

                    -- Clear temporary table for next batch
                    TRUNCATE TABLE temp_units;

                    -- Update remaining units
                    SET total_units_remaining = total_units_remaining - current_batch;
                END WHILE;

                -- Clean up by dropping the temporary table
                DROP TEMPORARY TABLE IF EXISTS temp_units;

                -- Re-enable foreign key checks and unique checks
                SET FOREIGN_KEY_CHECKS = 1;
                SET UNIQUE_CHECKS = 1;
                SET SQL_MODE = DEFAULT;

                COMMIT;

                -- Final debug information
                SELECT CONCAT(\'Total inserted: \', p_number_of_units, \' units\') AS final_result;
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
        //
    }
};
