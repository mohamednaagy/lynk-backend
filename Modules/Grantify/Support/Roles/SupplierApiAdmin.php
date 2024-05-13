<?php

namespace Modules\Grantify\Support\Roles;

use App\Enums\Action;
use App\Enums\Subject;

class SupplierApiAdmin
{
    public static array $basePermissions = [
        Subject::All => [
            Action::Manage,
        ],
    ];
}
