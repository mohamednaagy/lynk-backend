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
    public function up(): void
    {
        Schema::table('trader_orders', function (Blueprint $table) {
            $table->unsignedTinyInteger('status')->after('data');
            $table->dropColumn('type');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('trader_orders', function (Blueprint $table) {
            $table->string('type')->after('data');
            $table->dropColumn('status');
        });
    }
};
