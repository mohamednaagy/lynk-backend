<?php

namespace Modules\Permission\Database\Seeders;

namespace  Modules\Permission\Enums;

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;
use Modules\Permission\Enums\Role as EnumsRole;
use spatie\Permission\Models\Role;

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
        foreach (EnumsRole::asArray() as $value) {
            Role::findOrCreate($value, 'api');
        }
    }
}
