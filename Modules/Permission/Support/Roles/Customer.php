<?php

namespace Modules\Permission\Support\Roles;

use App\Enums\Role;

class Customer
{
    public static array $basePermissions = [
        Role::Customer => [

        ]
    ];
}
