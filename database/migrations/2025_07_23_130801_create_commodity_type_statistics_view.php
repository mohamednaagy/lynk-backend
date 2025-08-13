    <?php

    use Illuminate\Database\Migrations\Migration;

    return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
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

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP VIEW IF EXISTS commodity_type_statistics_view');
    }
};
