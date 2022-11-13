<?php

namespace Database\Seeders;

use App\Models\FinancingOrder;
use Illuminate\Database\Seeder;

class FinancingOrderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        FinancingOrder::factory(100, ['company_id' => 1])->create();
    }
}
