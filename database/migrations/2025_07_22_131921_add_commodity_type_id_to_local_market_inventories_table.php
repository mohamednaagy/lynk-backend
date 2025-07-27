<?php

use App\Models\LocalMarketInventory;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        
        Schema::table('local_market_inventories', function (Blueprint $table) {
            $table->unsignedBigInteger('commodity_type_id')->nullable()->after('commodity_item_id');
            $table->index('commodity_type_id');
            $table->foreign('commodity_type_id')
                ->references('id')
                ->on('commodity_types')
                ->onDelete('set null')
                ->onUpdate('cascade');
        });

        LocalMarketInventory::withTrashed()
        ->with(['item' => fn($q) => $q->withTrashed()])
        ->chunk(100, function ($inventories) {
            foreach ($inventories as $inventory) {
                $commodityTypeId = $inventory->item?->commodity_type_id;
                if ($commodityTypeId) {
                    DB::table('local_market_inventories')
                        ->where('id', $inventory->id)
                        ->update(['commodity_type_id' => $commodityTypeId]);
                }
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('local_market_inventories', function (Blueprint $table) {
            $table->dropForeign(['commodity_type_id']);
            $table->dropIndex(['commodity_type_id']);
            $table->dropColumn('commodity_type_id');
        });
    }
};
