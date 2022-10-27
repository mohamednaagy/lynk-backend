<?php

namespace App\Http\Controllers\Api\V1\Admin\Companies;

use App\Actions\Contracts\Companies\GetCompanyUsers;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Admin\Companies\GetCompanyUsersRequest;
use App\Models\Company;
use App\Transformers\UserTransformer;
use Illuminate\Http\JsonResponse;

class UserController extends Controller
{
    public function index(
        GetCompanyUsersRequest $getCompanyUsersRequest,
        Company $company,
        GetCompanyUsers $getCompanyUsers
    ): JsonResponse {
        return fractal($getCompanyUsers->handle($company), new UserTransformer)->respond();
    }
}
