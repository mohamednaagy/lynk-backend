<?php

namespace Modules\Permission\Support\Roles;

use Modules\Permission\Enums\Action;
use Modules\Permission\Enums\Subject;

class Admin
{
    public static array $basePermissions = [
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
    ];
}
