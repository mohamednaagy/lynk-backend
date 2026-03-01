<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::dropIfExists('trader_orders_view');
        DB::statement('
            CREATE VIEW trader_orders_view AS
            SELECT
                t.id AS id,
                t.financing_order_id,
                t.mode,
                t.reference,
                t.provider,
                t.created_at,
                ttl.effective_at as expire_at
            FROM trader_orders t
            LEFT JOIN trader_order_time_limits ttl
                ON ttl.id = (
                    SELECT id
                    FROM trader_order_time_limits
                    WHERE trader_order_id = t.id
                    ORDER BY id DESC
                    LIMIT 1
                );
       ');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trader_orders_view');
    }
};
