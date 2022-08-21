<?php

namespace App\Http\Controllers\Api\V1\Admins\Roles;

use App\Http\Controllers\Controller;
use Modules\Permission\Facades\Grantify;
use Spatie\Permission\Models\Permission;

class GetAllPermissions extends Controller
{
    public function __invoke()
    {
        return $this->successResponse(Grantify::transformPermissionsToSubjectAction(Permission::all()));
    }
}
