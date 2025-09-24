<?php

use App\Services\LocalMarket\Procedures\HoldOrderUnitProcedureManager;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        App::make(HoldOrderUnitProcedureManager::class)->build();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::unprepared('DROP PROCEDURE IF EXISTS hold_order_unit');
    }
};
