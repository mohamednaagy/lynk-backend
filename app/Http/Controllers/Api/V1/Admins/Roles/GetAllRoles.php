<?php

namespace App\Http\Controllers\Api\V1\Admins\Roles;

use Illuminate\Http\JsonResponse;
use Spatie\Permission\Models\Role;
use App\Http\Controllers\Controller;

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
