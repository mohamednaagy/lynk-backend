<?php

namespace App\Http\Controllers\Api\V1\Admin\Companies;

use App\Actions\Contracts\Companies\GetCompanyUsers;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Admin\Companies\GetCompanyUsersRequest;
use App\Transformers\UserTransformer;
use Illuminate\Http\JsonResponse;

class CompanyController extends Controller
{
    public function getCompanyUsers(
        GetCompanyUsersRequest $getCompanyUsersRequest,
        int $companyId,
        GetCompanyUsers $getCompanyUsers
    ): JsonResponse {
        return fractal($getCompanyUsers->handle($companyId), new UserTransformer)->respond();
    }
}
