<?php

namespace App\Http\Controllers\Api\V1\Admins\Roles;

use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use Modules\Permission\Facades\Grantify;
use Spatie\Permission\Models\Permission;

class GetAllPermissions extends Controller
{
    /**
     * @return JsonResponse
     */
    public function __invoke(): JsonResponse
    {
        return $this->successResponse(Grantify::transformPermissionsToSubjectAction(Permission::all()));
    }
}
