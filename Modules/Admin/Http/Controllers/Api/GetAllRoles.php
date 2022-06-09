<?php

namespace Modules\Admin\Http\Controllers\Api;

use Illuminate\Routing\Controller;
use Spatie\Permission\Models\Role;

class GetAllRoles extends Controller
{
    public function __invoke()
    {
        return  response()->jsonFormat(Role::all());
    }
}
