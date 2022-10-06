<?php

namespace App\Http\Controllers\Api\V1\Admins\Roles;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Spatie\Permission\Models\Role;

class GetAllRoles extends Controller
{
    /**
     * @return JsonResponse
     */
    public function __invoke(): JsonResponse
    {
        return $this->successResponse(Role::all()->toArray());
    }
}
