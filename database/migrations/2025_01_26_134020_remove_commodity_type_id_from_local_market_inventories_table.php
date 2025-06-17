<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
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
        Schema::table('local_market_inventories', function (Blueprint $table) {
            $table->dropForeign(['commodity_type_id']);
            $table->dropColumn('commodity_type_id');
            $table->dropColumn('min_price');
            $table->dropColumn('max_price');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('local_market_inventories', function (Blueprint $table) {
            $table->foreignId('commodity_type_id')->constrained('commodity_types');
            $table->decimal('min_price', 64, 2);
            $table->decimal('max_price', 64, 2);
        });
    }
};
