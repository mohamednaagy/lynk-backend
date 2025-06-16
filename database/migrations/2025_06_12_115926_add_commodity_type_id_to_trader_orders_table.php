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
        Schema::table('trader_orders', function (Blueprint $table) {
            $table->foreignId('commodity_type_id')->nullable()->after('provider')->constrained('commodity_types');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('trader_orders', function (Blueprint $table) {
            $table->dropForeign(['commodity_type_id']);
            $table->dropColumn('commodity_type_id');
        });
    }
};
