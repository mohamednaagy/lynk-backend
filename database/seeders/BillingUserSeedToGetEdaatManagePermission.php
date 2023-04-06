<?php

namespace Database\Seeders;

use App\Enums\Action;
use App\Enums\Area;
use App\Enums\Role;
use App\Enums\Subject;
use Illuminate\Database\Seeder;
use Modules\Grantify\Facades\Grantify;

class BillingUserSeedToGetEdaatManagePermission extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $permission = perm(Area::Lender, [Subject::LenderEdaatInvoices, Action::Manage]);
        Grantify::assignPermissionToRole(Role::LenderBilling, $permission);
    }
}
