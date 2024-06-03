<?php

namespace Database\Seeders;

use App\Models\Measurement;
use Illuminate\Database\Seeder;

class MeasurementTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $data = [
            't' => 'Tons',
            'kg' => 'Kilograms',
            'l' => 'Liters',
            'g' => 'Grams',
            'm3' => 'Cubic Meters',
            'ml' => 'Milliliters',
            'pc' => 'Pieces',
        ];

        foreach ($data as $key => $value) {
            Measurement::firstOrCreate(['name' => $value, 'symbol' => $key]);
        }
    }
}
