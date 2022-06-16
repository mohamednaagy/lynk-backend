<?php

namespace Modules\Permission\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;
use Modules\Permission\Enums\Action;
use Modules\Permission\Enums\Role as EnumsRole;
use Modules\Permission\Enums\Section;
use Modules\Permission\Enums\Subject;
use Spatie\Permission\Models\Permission;
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

        // seed all roles
        foreach (EnumsRole::asArray() as $role) {
            foreach (config('permission.guards') as $guard) {
                Role::findOrCreate($role, $guard);
            }
        }

        // seed all permissions in "Section-Subject.Action" format
        foreach (Section::asArray() as $section) {
            foreach (Subject::asArray() as $subject) {
                foreach (Action::asArray() as $action) {
                    foreach (config('permission.guards') as $guard) {
                        Permission::findOrCreate($section . '-' . $subject . '.' . $action, $guard);
                    }
                }
            }
        }
    }
}
