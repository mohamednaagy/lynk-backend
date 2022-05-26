<?php

namespace Modules\Permission\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;
use Modules\Permission\Enums\Role as EnumsRole;
use Spatie\Permission\Models\Role;

class PermissionDatabaseSeeder extends Seeder
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
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        foreach (EnumsRole::asArray() as $role) {
            foreach (config('permission.guards') as $guard) {
                Role::findOrCreate($role, $guard);
            }
        }
    }
}
