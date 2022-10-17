<?php

namespace App\Http\Controllers\Api\V1\Admin\Roles;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Grantify\Facades\Grantify;
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
