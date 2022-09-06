<?php

namespace App\Http\Controllers\Api\V1\Admins\Roles;

use App\Http\Controllers\Controller;
use Spatie\Permission\Models\Role;

class GetAllRoles extends Controller
{
    public function __invoke()
    {
        return $this->successResponse(Role::all()->toArray());
    }
}
