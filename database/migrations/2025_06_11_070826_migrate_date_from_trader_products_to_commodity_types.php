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
            $commodityTypesData = $this->prepareCommodityTypesData($traderProducts);

            $this->insertCommodityTypes($commodityTypesData, $traderProducts);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error migrating data from trader products to commodity types: '.$e->getMessage());
            throw $e;
        }
    }

    /**
     * Prepare commodity types data from trader products.
     *
     * @param  \Illuminate\Support\Collection  $traderProducts
     * @return array
     */
    private function prepareCommodityTypesData($traderProducts)
    {
        return $traderProducts->map(function ($traderProduct) {
            return [
                'name' => $traderProduct->name,
                'description' => $traderProduct->name,
                'unique_name' => $traderProduct->code,
                'provider' => $traderProduct->provider,
                'status' => $this->mapTraderProductStatus($traderProduct->status),
                'created_at' => $traderProduct->created_at,
                'updated_at' => $traderProduct->updated_at,
            ];
        })->toArray();
    }

    /**
     * Map trader product status to commodity type status.
     *
     * @param  TraderProductStatus  $status
     * @return CommodityTypeStatus
     */
    private function mapTraderProductStatus($status)
    {
        return $status->is(TraderProductStatus::Enabled)
            ? CommodityTypeStatus::Active
            : CommodityTypeStatus::Inactive;
    }

    /**
     * Insert commodity types and validate the insertion.
     *
     * @param  array  $commodityTypesData
     * @param  \Illuminate\Support\Collection  $traderProducts
     *
     * @throws \Exception
     */
    private function insertCommodityTypes($commodityTypesData, $traderProducts)
    {
        CommodityType::insert($commodityTypesData);

        $insertedDataCount = count($commodityTypesData);
        $expectedCount = count($traderProducts);

        if ($insertedDataCount !== $expectedCount) {
            $errorMessage = sprintf(
                'Error migrating data from trader products to commodity types: %d out of %d trader products migrated successfully.',
                $insertedDataCount,
                $expectedCount
            );

            Log::error($errorMessage);
            throw new \Exception($errorMessage);
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
