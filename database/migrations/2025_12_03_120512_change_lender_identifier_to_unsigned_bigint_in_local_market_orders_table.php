<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('local_market_orders', function (Blueprint $table) {
            $table->unsignedBigInteger('lender_identifier')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('local_market_orders', function (Blueprint $table) {
            $table->smallInteger('lender_identifier')->nullable()->change();
        });
    }
};
