<?php

namespace App\Http\Controllers\Api\V1\Admin\Companies;

use App\Actions\Contracts\Companies\GetCompanies;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Admin\Companies\GetCompaniesRequest;
use App\Transformers\CompanyTransformer;
use Illuminate\Http\JsonResponse;

class CompanyController extends Controller
{
    public function index(
        GetCompaniesRequest $getCompaniesRequest,
        GetCompanies $getCompanies
    ): JsonResponse {
        return fractal($getCompanies->handle(), new CompanyTransformer())->respond();
    }
}
