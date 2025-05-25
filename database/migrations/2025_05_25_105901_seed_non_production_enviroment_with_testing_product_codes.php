<?php

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
        if (app()->environment('production')) {
            return;
        }

        $productCodes = [
            [
                'code' => 'PR-C-MSIA14',
                'name' => json_encode([
                    'en' => 'Petroleum A',
                    'ar' => 'بترول A',
                ]),
                'provider' => 'bursam',
                'order' => 1,
                'status' => 'ENABLED',
            ],
            [
                'code' => 'PR-Z-MSIA14',
                'name' => json_encode([
                    'en' => 'Petroleum B',
                    'ar' => 'بترول B',
                ]),
                'provider' => 'bursam',
                'order' => 1,
                'status' => 'ENABLED',
            ],
        ];

        DB::table('trader_products')->insert($productCodes);
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
