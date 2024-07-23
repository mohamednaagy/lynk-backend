<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        /*
         *  Seeding modules first ex: Grantify
         *  Roles and permission required before seeding user
        */
        Artisan::call('module:seed');

        $user = User::create([
            'first_name' => 'Super',
            'last_name' => 'Admin',
            'phone_number' => '+966138823616',
            'email' => 'admin@bim.com',
            'password' => bcrypt('12345678'),
        ]);

        $user->assignRole(Role::Admin);
    }
}
