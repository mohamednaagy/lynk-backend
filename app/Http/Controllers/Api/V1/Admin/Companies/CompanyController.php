<?php

namespace App\Http\Controllers\Api\V1\Admin\Companies;

use App\Actions\Contracts\Companies\CreateCompany;
use App\Actions\Contracts\Companies\GetCompanies;
use App\Actions\Contracts\Companies\UpdateCompany;
use App\Actions\Contracts\GetSettingsClassInstance;
use App\Enums\Area;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Admin\Companies\StoreCompanyRequest;
use App\Http\Requests\V1\Admin\Companies\UpdateCompanyRequest;
use App\Jobs\Company\CompanyRegisteredNotification;
use App\Models\Company;
use App\Transformers\CompanyTransformer;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class CompanyController extends Controller
{
    /**
     * @param  GetCompanies  $getCompanies
     * @return JsonResponse
     */
    public function index(
        // __REVIEW__ Change name to GetPaginatedCompanies $getPaginatedCompanies
        GetCompanies $getCompanies
    ): JsonResponse {
        return fractal($getCompanies->handle(), new CompanyTransformer())
            ->parseIncludes([
                'id',
                'name',
                'status',
                'orders_count',
                'created_at',
                'order_cost',
            ])
            ->respond();
    }

    /**
     * @param  StoreCompanyRequest  $createCompanyRequest
     * @param  CreateCompany  $createCompany
     * @param  GetSettingsClassInstance  $getSettingsClassInstance
     * @return JsonResponse
     */
    public function store(
        StoreCompanyRequest $createCompanyRequest,
        CreateCompany $createCompany,
        GetSettingsClassInstance $getSettingsClassInstance
    ): JsonResponse {
        // __REVIEW__ use DB::transaction(...)
        $data = $createCompanyRequest->validated();
        $data['status'] = $getSettingsClassInstance->handle(Area::Lender)->default_company_status_created_by_operation;

        $company = $createCompany->handle($data);
        dispatch(new CompanyRegisteredNotification($company));

        // __REVIEW__ return company with transformer
        return $this->successResponse();
    }

    /**
     * @param  Company  $company
     * @return JsonResponse
     */
    public function show(Company $company): JsonResponse
    {
        return fractal($company, new CompanyTransformer())
            ->parseIncludes([
                'id',
                'name',
                'status',
                'created_at',
                'unique_name',
                'company_cr',
                'does_order_require_approval',
                'order_cost',
            ])
            ->respond();
    }

    /**
     * @param  UpdateCompanyRequest  $updateCompanyRequest
     * @param  UpdateCompany  $updateCompany
     * @param  Company  $company
     * @return JsonResponse
     */
    public function update(
        UpdateCompanyRequest $updateCompanyRequest,
        UpdateCompany $updateCompany,
        Company $company
    ): JsonResponse {
        // __REVIEW__ use DB::transaction(...)
        $updateCompany->handle($company, $updateCompanyRequest->validated());

        return $this->successResponse();
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  Company  $company
     * @return JsonResponse
     *
     * @throws \Throwable
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
