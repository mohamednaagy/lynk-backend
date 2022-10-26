<?php

namespace Modules\Grantify\Support\Roles;

use App\Enums\Action;
use App\Enums\Subject;

class LenderSupervisor
{
    public static array $basePermissions = [
        Subject::Dashboard => [
            Action::Show,
        ],
        Subject::LenderOrders => [
            Action::Manage,
        ],
    ];
}
