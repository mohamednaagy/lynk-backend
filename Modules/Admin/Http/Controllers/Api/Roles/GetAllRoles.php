<?php

namespace Modules\Admin\Http\Controllers\Api\Roles;

use Illuminate\Routing\Controller;
use Spatie\Permission\Models\Role;
use function response;

class GetAllRoles extends Controller
{
    public function __invoke()
    {
        return  response()->jsonFormat(Role::all());
    }
}
