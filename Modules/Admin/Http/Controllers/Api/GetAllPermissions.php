<?php

namespace Modules\Admin\Http\Controllers\Api;

use Illuminate\Routing\Controller;
use Modules\Permission\Facades\Grantify;
use Spatie\Permission\Models\Permission;

class GetAllPermissions extends Controller
{
    public function __invoke()
    {
        return response()->jsonFormat( Grantify::transformPermissionsToSubjectAction(Permission::all()) );
    }
}
