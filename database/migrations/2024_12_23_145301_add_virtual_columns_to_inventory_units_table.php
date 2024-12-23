<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement("
            ALTER TABLE local_market_inventory_units
            ADD COLUMN previous_company_id_owner_0 VARCHAR(100) GENERATED ALWAYS AS (JSON_UNQUOTE(JSON_EXTRACT(previous_company_id_owners, '$[0]'))) STORED
        ");

        DB::statement("
            ALTER TABLE local_market_inventory_units
            ADD COLUMN previous_company_id_owner_1 VARCHAR(100) GENERATED ALWAYS AS (JSON_UNQUOTE(JSON_EXTRACT(previous_company_id_owners, '$[1]'))) STORED
        ");

        DB::statement("
            ALTER TABLE local_market_inventory_units
            ADD COLUMN previous_company_id_owner_2 VARCHAR(100) GENERATED ALWAYS AS (JSON_UNQUOTE(JSON_EXTRACT(previous_company_id_owners, '$[2]'))) STORED
        ");

        DB::statement("
            ALTER TABLE local_market_inventory_units
            ADD COLUMN previous_company_id_owner_3 VARCHAR(100) GENERATED ALWAYS AS (JSON_UNQUOTE(JSON_EXTRACT(previous_company_id_owners, '$[3]'))) STORED
        ");

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('local_market_inventory_units', function (Blueprint $table) {
            for ($i = 0; $i < 4; $i++) {
                $table->dropColumn("previous_company_id_owner_$i");
            }
        });
    }
};
