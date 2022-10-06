<?php

namespace Modules\Grantify\Support\Roles;

use App\Enums\Action;
use App\Enums\Subject;

class Admin
{
    public static array $basePermissions = [
        Subject::Admins => [
            Action::Index,
            Action::Create,
            Action::Show,
            Action::Edit,
            Action::Delete,
        ],
        Subject::Roles => [
            Action::Index,
        ],
        Subject::Permissions => [
            Action::Index,
        ],
        Subject::Customers => [
            Action::Index,
            Action::Create,
            Action::Show,
            Action::Edit,
            Action::Delete,
        ],
    ];
}
