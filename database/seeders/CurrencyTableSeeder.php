<?php

namespace Database\Seeders;

use App\Models\Currency;
use Illuminate\Database\Seeder;

class CurrencyTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $data = [
            'SAR',
        ];

        foreach ($data as $currency) {
            Currency::create(['name' => $currency]);
        }
    }
}
