<?php

namespace App\Http\Controllers\Api\V1\Admin\Admins;

use App\Actions\Contracts\GetPaginatedUsersByRole;
use App\Http\Controllers\Controller;
use App\Enums\Action;
use App\Enums\Area;
use App\Enums\Subject;
use App\Transformers\UserTransformer;

class AdminLiteList extends Controller
{

    public function __construct()
    {
        $this->middleware(
            'permission:' .
            perm(Area::SuperAdmin, [Subject::Admins, Action::Index, Action::Manage])
        );
    }
    public function __invoke(GetPaginatedUsersByRole $getPaginatedUsersByRole)
    {
        $admins = $getPaginatedUsersByRole->handle(Area::roles(Area::SuperAdmin))->get([
            'id',
            'first_name',
            'last_name',
        ]);

        return fractal($admins, new UserTransformer(Area::SuperAdmin))
            ->parseIncludes([
                'id',
                'full_name',
            ])->respond();
    }
}
