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

        if (! Schema::hasColumn('commodity_items', 'commodity_type_id')) {
            Schema::table('commodity_items', function (Blueprint $table) {
                $table->unsignedBigInteger('commodity_type_id')->nullable();
                $table->foreign('commodity_type_id')->references('id')->on('commodity_types');
            });
        }

        $items = \App\Models\CommodityItem::get();
        foreach ($items as $item) {
            $get_type_id = \Illuminate\Support\Facades\DB::table('commodity_item_types')->where('commodity_item_id', $item->id)->first()?->commodity_type_id;
            $item->update(['commodity_type_id' => $get_type_id]);
        }

        Schema::dropIfExists('commodity_item_types');

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
};
