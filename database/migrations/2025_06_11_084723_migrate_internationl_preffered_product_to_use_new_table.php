<?php

use App\Actions\Contracts\UpdateSettings;
use App\Models\CommodityType;
use App\Models\TraderProduct;
use App\Settings\Classes\InternationalMurabahaSetting;
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

            $globalPreferredCommodityType = app(InternationalMurabahaSetting::class)->bursam_default_preferred_commodity_type;

            $traderProduct = TraderProduct::findOrFail($globalPreferredCommodityType);
            $commodityType = CommodityType::where('unique_name', $traderProduct->code)->firstOrFail();

            $data = [
                'bursam_default_preferred_commodity_type' => $commodityType->id,
                'area' => 'InternationalMurabaha',
            ];

            app(UpdateSettings::class)->handle($data);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error migrating international preferred commodity type: '.$e->getMessage());
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
        // Note: This is a one-way migration and should not be reversed.
    }
};
