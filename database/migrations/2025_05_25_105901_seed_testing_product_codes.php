<?php

use App\Models\TraderProduct;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        TraderProduct::truncate();
        DB::table('trader_products')->insert($this->getProductCodes());
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        TraderProduct::truncate();
    }

    private function getProductCodes()
    {
        $time = now()->format('Y-m-d H:i:s');
        $nonProductionEnvironmentsData = [
            [
                'code' => 'AL-MSIA- 23',
                'name' => json_encode([
                    'en' => 'Aluminium',
                    'ar' => 'الألومنيوم',
                ]),
                'provider' => 'bursam',
                'order' => 1,
                'status' => 'ENABLED',
                'created_at' => $time,
                'updated_at' => $time,
            ],
            [
                'code' => 'CPO-MSIA-09',
                'name' => json_encode([
                    'en' => 'Crude Palm Oil',
                    'ar' => 'زيت النخيل الخام',
                ]),
                'provider' => 'bursam',
                'order' => 1,
                'status' => 'ENABLED',
                'created_at' => $time,
                'updated_at' => $time,
            ],
            [
                'code' => 'PB-LEAD- 19',
                'name' => json_encode([
                    'en' => 'Lead',
                    'ar' => 'رصاص',
                ]),
                'provider' => 'bursam',
                'order' => 1,
                'status' => 'ENABLED',
                'created_at' => $time,
                'updated_at' => $time,
            ],
            [
                'code' => 'PR-B-MSIA14',
                'name' => json_encode([
                    'en' => 'Plastic Resin B',
                    'ar' => 'راتنج بلاستيك B',
                ]),
                'provider' => 'bursam',
                'order' => 1,
                'status' => 'ENABLED',
                'created_at' => $time,
                'updated_at' => $time,
            ],
            [
                'code' => 'PR-Z-MSIA14',
                'name' => json_encode([
                    'en' => 'NoCommodity_TEST',
                    'ar' => 'لا يوجد منتج',
                ]),
                'provider' => 'bursam',
                'order' => 1,
                'status' => 'ENABLED',
                'created_at' => $time,
                'updated_at' => $time,
            ],
            [
                'code' => 'PR-C-MSIA14',
                'name' => json_encode([
                    'en' => 'NoInventory_TEST',
                    'ar' => 'لا يوجد مخزون',
                ]),
                'provider' => 'bursam',
                'order' => 1,
                'status' => 'ENABLED',
                'created_at' => $time,
                'updated_at' => $time,
            ],
        ];

        $productionEnvironmentsData = [
            [
                'code' => 'AL-MSIA- 23',
                'name' => json_encode([
                    'en' => 'Aluminium',
                    'ar' => 'الألومنيوم',
                ]),
                'provider' => 'bursam',
                'order' => 1,
                'status' => 'ENABLED',
                'created_at' => $time,
                'updated_at' => $time,
            ],
            [
                'code' => 'CPO-MSIA-09',
                'name' => json_encode([
                    'en' => 'Crude Palm Oil',
                    'ar' => 'زيت النخيل الخام',
                ]),
                'provider' => 'bursam',
                'order' => 1,
                'status' => 'ENABLED',
                'created_at' => $time,
                'updated_at' => $time,
            ],
            [
                'code' => 'PB-LEAD -19',
                'name' => json_encode([
                    'en' => 'Lead',
                    'ar' => 'رصاص',
                ]),
                'provider' => 'bursam',
                'order' => 1,
                'status' => 'ENABLED',
                'created_at' => $time,
                'updated_at' => $time,
            ],
            [
                'code' => 'PR-B MSIA14',
                'name' => json_encode([
                    'en' => 'Plastic Resin B',
                    'ar' => 'راتنج بلاستيك B',
                ]),
                'provider' => 'bursam',
                'order' => 1,
                'status' => 'ENABLED',
                'created_at' => $time,
                'updated_at' => $time,
            ],

        ];

        return app()->environment('production') ? $productionEnvironmentsData : $nonProductionEnvironmentsData;
    }
};
