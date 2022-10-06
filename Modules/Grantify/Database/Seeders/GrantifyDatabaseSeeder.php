<?php

namespace Modules\Grantify\Database\Seeders;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use Modules\Grantify\Facades\GrantifySeeder;
use Spatie\Permission\PermissionRegistrar;

class GrantifyDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Model::unguard();

        // $this->call("OthersTableSeeder");

        // reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // seed all roles
        GrantifySeeder::seedRoles();

        // seed all permissions in "Section-Subject.Action" format
        GrantifySeeder::seedPermissions(true);
    }
}
