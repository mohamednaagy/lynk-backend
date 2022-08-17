<?php

namespace Modules\Permission\Support\Roles;

use Modules\Permission\Enums\Role;

class Customer
{
    public static array $basePermissions = [
        Role::Customer => [

        ]
    ];
}
