<?php

namespace Modules\Grantify\Support\Roles;

use App\Enums\Action;
use App\Enums\Subject;

class Manager
{
    public static array $basePermissions = [
        Subject::LenderUsers => [
            Action::Manage,
        ],
    ];
}
