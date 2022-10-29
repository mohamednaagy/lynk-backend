<?php

namespace App\Http\Controllers\Api\V1\Admin\Companies;

use App\Actions\Contracts\Companies\CreateCompany as CreateCompanyInterface;
use App\Actions\Contracts\Companies\GetCompanies;
use App\Actions\Contracts\Companies\UpdateCompany;
use App\Enums\CompanyStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Admin\Companies\GetCompaniesRequest;
use App\Http\Requests\V1\Admin\Companies\UpdateCompanyRequest;
use App\Http\Requests\V1\Company\CreateCompanyRequest;
use App\Models\Company;
use App\Transformers\CompanyTransformer;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

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

    public function update(UpdateCompanyRequest $updateCompanyRequest, UpdateCompany $updateCompany, Company $company)
    {
        $updateCompany->handle($company, $updateCompanyRequest->validated());

        return $this->successResponse();
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return JsonResponse
     */
    public function destroy(Company $company): JsonResponse
    {
        DB::transaction(function () use ($company) {
            $company->update(['unique_name' => null]);
            $company->delete();
        });

        return $this->successResponse();
    }
}
