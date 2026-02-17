<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement('DROP VIEW IF EXISTS trader_orders_view');

        DB::statement('
            CREATE VIEW trader_orders_view AS
            SELECT
                t.id AS id,
                t.financing_order_id,
                t.mode,
                t.provider,
                ttl.effective_at AS expire_at,
                tod.purchasing_commodity AS purchasing_commodity,
                tod.contract_signed AS contract_signed,
                tod.commodity_sold_to_customer AS commodity_sold_to_customer,
                tod.client_wakala AS client_wakala,
                tod.murabaha_sale_completed AS murabaha_sale_completed
            FROM trader_orders t
            LEFT JOIN trader_order_time_limits ttl
                ON ttl.id = (
                    SELECT id
                    FROM trader_order_time_limits
                    WHERE trader_order_id = t.id
                    ORDER BY id DESC
                    LIMIT 1
                )
            LEFT JOIN trader_order_durations tod ON tod.trader_order_id = t.id
        ');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP VIEW IF EXISTS trader_orders_view');

        DB::statement('
            CREATE VIEW trader_orders_view AS
            SELECT
                t.id AS id,
                t.financing_order_id,
                t.mode,
                t.provider,
                ttl.effective_at AS expire_at
            FROM trader_orders t
            LEFT JOIN trader_order_time_limits ttl
                ON ttl.id = (
                    SELECT id
                    FROM trader_order_time_limits
                    WHERE trader_order_id = t.id
                    ORDER BY id DESC
                    LIMIT 1
                )
        ');
    }
};
