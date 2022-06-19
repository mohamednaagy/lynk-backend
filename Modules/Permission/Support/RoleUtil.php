<?php

namespace Modules\Permission\Support;

use Modules\Permission\Enums\Role;
use Modules\Permission\Support\Roles\Admin;
use Modules\Permission\Support\Roles\Customer;

class RoleUtil
{
    public static array $roleMap = [
        Role::Admin => Admin::class,
        Role::Customer => Customer::class,
    ];

}
