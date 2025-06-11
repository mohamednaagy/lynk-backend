<?php

use App\Enums\CommodityTypeStatus;
use App\Enums\TraderProductStatus;
use App\Models\CommodityType;
use App\Models\TraderProduct;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        try {
            DB::beginTransaction();
            $traderProducts = TraderProduct::all();
            $data = [];
            foreach ($traderProducts as $traderProduct) {
                $data[] = [
                    'name' => $traderProduct->name,
                    'description' => $traderProduct->name,
                    'unique_name' => $traderProduct->code,
                    'provider' => $traderProduct->provider,
                    'status' => ($traderProduct->status->is(TraderProductStatus::Enabled)) ? CommodityTypeStatus::Active : CommodityTypeStatus::Inactive,
                    'created_at' => $traderProduct->created_at,
                    'updated_at' => $traderProduct->updated_at,
                ];
            }

            CommodityType::insert($data);
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error migrating data from trader products to commodity types: '.$e->getMessage());
            throw $e;
        }
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
