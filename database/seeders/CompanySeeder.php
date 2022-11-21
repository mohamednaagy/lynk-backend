<?php

namespace Database\Seeders;

use App\Models\Company;
use Faker\Factory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CompanySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $faker = Factory::create();

        $companies = Company::factory(5)->create();

        $companies->map(function ($company) use ($faker) {
            DB::table('domains')->insert([
                'domain' => $faker->unique()->domainName,
                'company_id' => $company->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });
    }
}
