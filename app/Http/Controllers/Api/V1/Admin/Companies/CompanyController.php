<?php

namespace App\Http\Controllers\Api\V1\Admin\Companies;

use App\Actions\Contracts\Companies\CreateCompany as CreateCompanyInterface;
use App\Actions\Contracts\Companies\GetCompanies;
use App\Enums\CompanyStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Admin\Companies\GetCompaniesRequest;
use App\Http\Requests\V1\Company\CreateCompanyRequest;
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

    /**
     * @param  CreateCompanyRequest  $createCompanyRequest
     * @param  CreateCompanyInterface  $createCompany
     * @return JsonResponse
     */
    public function store(
        CreateCompanyRequest $createCompanyRequest,
        CreateCompanyInterface $createCompany
    ): JsonResponse {
        $data = $createCompanyRequest->validated();
        $data['status'] = CompanyStatus::Approved;
        $data['does_order_require_approval'] = true;

        $createCompany->handle($data);

        return $this->successResponse();
    }
}
