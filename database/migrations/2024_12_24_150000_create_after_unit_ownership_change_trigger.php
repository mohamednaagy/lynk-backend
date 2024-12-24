<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class CreateAfterUnitOwnershipChangeTrigger extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::unprepared('
            CREATE TRIGGER after_unit_ownership_change
            AFTER UPDATE ON local_market_inventory_units
            FOR EACH ROW
            BEGIN
                IF (NEW.current_owner != OLD.current_owner OR NEW.current_owner_type != OLD.current_owner_type) THEN
                    INSERT INTO local_market_unit_ownership (
                        unit_id,
                        previous_owner,
                        previous_owner_type,
                        current_owner,
                        current_owner_type,
                        created_at,
                        updated_at
                    ) VALUES (
                        NEW.id,
                        OLD.current_owner,
                        OLD.current_owner_type,
                        NEW.current_owner,
                        NEW.current_owner_type,
                        NOW(),
                        NOW()
                    );
                END IF;
            END
        ');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::unprepared('DROP TRIGGER IF EXISTS after_unit_ownership_change');
    }
}
