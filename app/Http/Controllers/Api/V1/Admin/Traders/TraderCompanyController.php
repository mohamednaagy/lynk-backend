<?php

namespace App\Http\Controllers\Api\V1\Admin\Traders;

use App\Actions\Contracts\Companies\CreateCompany;
use App\Actions\Contracts\Companies\GetPaginatedCompanies;
use App\Actions\Contracts\Companies\UpdateCompany;
use App\Actions\Contracts\GetSettingsClassInstance;
use App\Actions\Contracts\Wallets\CreateWallet;
use App\Enums\Action;
use App\Enums\Area;
use App\Enums\CompanyType;
use App\Enums\Subject;
use App\Enums\WalletType;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Trader\Companies\StoreCompanyRequest;
use App\Http\Requests\V1\Trader\Companies\UpdateCompanyRequest;
use App\Models\Company;
use App\Support\Money\Money;
use App\Transformers\CompanyTransformer;
use Illuminate\Support\Facades\DB;

class TraderCompanyController extends Controller
{
    public function __construct()
    {
        $this->middleware(
            'permission:'.
                perm(Area::Trader, [Subject::TraderCompanies, Action::Index, Action::Manage])
        )->only('index');

        $this->middleware(
            'permission:'.
                perm(Area::Trader, [Subject::TraderCompanies, Action::Create, Action::Manage])
        )->only('store');

        $this->middleware(
            'permission:'.
                perm(Area::Trader, [Subject::TraderCompanies, Action::Show, Action::Manage])
        )->only('show');

        $this->middleware(
            'permission:'.
                perm(Area::Trader, [Subject::TraderCompanies, Action::Edit, Action::Manage])
        )->only('update');
    }

    public function index(GetPaginatedCompanies $getPaginatedCompanies)
    {
        $getPaginatedCompanies->setType(CompanyType::Trader);

        return fractal($getPaginatedCompanies->handle(), new CompanyTransformer())
            ->parseIncludes([
                'id',
                'name',
                'unique_name',
                'orders_count',
            ])
            ->respond();
    }

    public function store(
        StoreCompanyRequest $request,
        GetSettingsClassInstance $getSettingsClassInstance,
        CreateCompany $createCompany,
        CreateWallet $createWallet
    ) {
        return DB::multipleTransaction(
            function () use ($request, $getSettingsClassInstance, $createCompany, $createWallet) {
                $traderSetting = $getSettingsClassInstance->handle(Area::Trader);

                $data = array_merge($request->validated(), [
                    'status' => $traderSetting->default_company_status_created_by_operation,
                    'order_cost' => $traderSetting->default_order_cost,
                    'type' => CompanyType::Trader,
                ]);

                $company = $createCompany->handle($data);

                $createWallet->handle($company, WalletType::CompanyWallet, Money::getDefaultCurrency());

                return fractal($company, new CompanyTransformer())
                    ->parseIncludes([
                        'id',
                        'name',
                        'unique_name',
                        'driver',
                    ])
                    ->respond();
            }
        );
    }

    public function show(Company $company)
    {
        return fractal($company, new CompanyTransformer())
            ->parseIncludes([
                'id',
                'name',
                'unique_name',
                'driver',
            ])
            ->respond();
    }

    public function update(
        UpdateCompanyRequest $request,
        Company $company,
        UpdateCompany $updateCompany
    ) {
        $updateCompany->handle($company, $request->validated());

        return $this->successResponse([]);
    }
}
