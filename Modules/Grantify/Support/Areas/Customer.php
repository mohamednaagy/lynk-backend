<?php

namespace Modules\Grantify\Support\Areas;

use App\Enums\Role;

class Customer
{
    public static array $roles = [
        Role::Customer,
    ];

    public static array $basePermissions = [

    ];
}
