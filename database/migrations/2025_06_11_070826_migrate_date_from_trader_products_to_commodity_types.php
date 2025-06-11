<?php

use App\Enums\CommodityTypeStatus;
use App\Enums\TraderProductStatus;
use App\Models\CommodityType;
use App\Models\TraderProduct;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $time = now();
        $traderProducts = TraderProduct::all();
        $data = [];
        foreach ($traderProducts as $traderProduct) {
            $data[] = [
                'name' => $traderProduct->name,
                'description' => $traderProduct->name,
                'unique_name' => $traderProduct->code,
                'provider' => $traderProduct->provider,
                'status' => ($traderProduct->status->is(TraderProductStatus::Enabled)) ? CommodityTypeStatus::Active : CommodityTypeStatus::Inactive,
                'created_at' => $time,
                'updated_at' => $time,
            ];
        }

        CommodityType::insert($data);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //Note that we should treat this as a one-way migration.
    }
};
