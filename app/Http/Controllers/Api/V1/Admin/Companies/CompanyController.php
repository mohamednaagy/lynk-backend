<?php

namespace App\Http\Controllers\Api\V1\Admin\Companies;

use App\Actions\Contracts\Companies\CreateCompany;
use App\Actions\Contracts\Companies\GetPaginatedCompanies;
use App\Actions\Contracts\Companies\UpdateCompany;
use App\Actions\Contracts\GetSettingsClassInstance;
use App\Enums\Action;
use App\Enums\Area;
use App\Enums\Subject;
use App\Enums\WalletType;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Admin\Companies\StoreCompanyRequest;
use App\Http\Requests\V1\Admin\Companies\UpdateCompanyRequest;
use App\Models\Company;
use App\Transformers\CompanyTransformer;
use Cknow\Money\Money;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class CompanyController extends Controller
{
    public function __construct()
    {
        $this->middleware(
            'permission:'.
            perm(Area::Lender, [Subject::Lenders, Action::Index, Action::Manage])
        )->only('index');

        $this->middleware(
            'permission:'.
            perm(Area::Lender, [Subject::Lenders, Action::Show, Action::Manage])
        )->only('show');

        $this->middleware(
            'permission:'.
            perm(Area::Lender, [Subject::Lenders, Action::Create, Action::Manage])
        )->only('store');

        $this->middleware(
            'permission:'.
            perm(Area::Lender, [Subject::Lenders, Action::Edit, Action::Manage])
        )->only('update');

        $this->middleware(
            'permission:'.
            perm(Area::Lender, [Subject::Lenders, Action::Delete, Action::Manage])
        )->only('update');
    }

    /**
     * @param  GetPaginatedCompanies  $getPaginatedCompanies
     * @return JsonResponse
     */
    public function index(
        GetPaginatedCompanies $getPaginatedCompanies
    ): JsonResponse {
        return fractal($getPaginatedCompanies->handle(), new CompanyTransformer())
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
        return DB::transaction(function () use ($createCompanyRequest, $getSettingsClassInstance, $createCompany) {
            $data = $createCompanyRequest->validated();
            $data['status'] = $getSettingsClassInstance->handle(Area::Lender)->default_company_status_created_by_operation;

            $company = $createCompany->handle($data);

            $company->createWallet(WalletType::CompanyWallet, Money::getDefaultCurrency());

            $company = $createCompany->handle($data);

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
        });
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
        return DB::transaction(function () use ($updateCompanyRequest, $updateCompany, $company) {
            $updateCompany->handle($company, $updateCompanyRequest->validated());

            return $this->successResponse();
        });
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
