<?php

use App\Enums\LocalMarket\InventoryStatus;
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
        Schema::table('inventories', function (Blueprint $table) {
            // Change the default value of the status column
            $table->string('status')->default(InventoryStatus::Pending)->comment(InventoryStatus::Pending.'|'.InventoryStatus::Active.'|'.InventoryStatus::Inactive)->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('inventories', function (Blueprint $table) {
            // Revert the status column back to its original state
            $table->string('status')->default(InventoryStatus::Pending)->comment(InventoryStatus::Pending.'|'.InventoryStatus::Active.'|'.InventoryStatus::Inactive)->change();
        });
    }
};
