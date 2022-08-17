<?php

namespace Modules\Admin\Http\Controllers\Api\V1\Roles;

use Illuminate\Routing\Controller;
use Modules\Permission\Facades\Grantify;
use Spatie\Permission\Models\Permission;
use function response;

class GetAllPermissions extends Controller
{
    public function __invoke()
    {
        return response()->jsonFormat( Grantify::transformPermissionsToSubjectAction(Permission::all()) );
    }
}
