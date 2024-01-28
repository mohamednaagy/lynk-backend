<?php

namespace Modules\Grantify\Support\Roles;

use App\Enums\Action;
use App\Enums\Subject;

class LenderApiAdmin
{
    public static array $basePermissions = [
        Subject::All => [
            Action::Manage,
            Action::Approve,
        ],
    ];
}
