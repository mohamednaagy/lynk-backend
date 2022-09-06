<?php

namespace Modules\Permission\Support\Roles;

use App\Enums\Action;
use App\Enums\Role;
use App\Enums\Subject;

class Admin
{
    public static array $basePermissions = [
        Role::Admin => [
            Subject::Admins => [
                Action::Index,
                Action::Create,
                Action::Show,
                Action::Edit,
                Action::Delete
            ],
            Subject::Customers => [
                Action::Index,
                Action::Create,
                Action::Show,
                Action::Edit,
                Action::Delete
            ]
        ]
    ];
}
