<?php

namespace App\Http\Controllers\Api\V1\Admin\Lenders;

use App\Actions\Contracts\Companies\BuildPaginatedCompaniesQuery;
use App\Actions\Contracts\Companies\CalculateVatAmount;
use App\Actions\Contracts\Companies\CreateCompany;
use App\Actions\Contracts\Companies\UpdateCompany;
use App\Actions\Contracts\GetSettingsClassInstance;
use App\Enums\Action;
use App\Enums\Area;
use App\Enums\CompanyType;
use App\Enums\Subject;
use App\Enums\WalletType;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Admin\Companies\CompaniesListFilterRequest;
use App\Http\Requests\V1\Admin\Companies\PartialUpdateCompanyRequest;
use App\Http\Requests\V1\Admin\Companies\StoreCompanyRequest;
use App\Http\Requests\V1\Admin\Companies\UpdateCompanyRequest;
use App\Models\Lender;
use App\Transformers\CompanyTransformer;
use Cknow\Money\Money;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class LenderController extends Controller
{
    public function __construct()
    {
        $this->middleware(
            'permission:'.
            perm(Area::SuperAdmin, [Subject::Lenders, Action::Index, Action::Manage])
        )->only('index');

        $this->middleware(
            'permission:'.
            perm(Area::SuperAdmin, [Subject::FinancingOrders, Action::Show, Action::Manage]).
            '|'.
            perm(Area::SuperAdmin, [Subject::Lenders, Action::Show, Action::Manage])
        )->only('show');

        $this->middleware(
            'permission:'.
            perm(Area::SuperAdmin, [Subject::Lenders, Action::Create, Action::Manage])
        )->only('store');

        $this->middleware(
            'permission:'.
            perm(Area::SuperAdmin, [Subject::Lenders, Action::Edit, Action::Manage])
        )->only('update', 'partialUpdate');

        $this->middleware(
            'permission:'.
            perm(Area::SuperAdmin, [Subject::Lenders, Action::Delete, Action::Manage])
        )->only('destroy');
    }

    public function index(
        CompaniesListFilterRequest $request,
        BuildPaginatedCompaniesQuery $buildPaginatedCompaniesQuery
    ): JsonResponse {
        $request->validated();
        $companies = $buildPaginatedCompaniesQuery->setType(CompanyType::Lender)
            ->handle()
            ->paginate();

        return fractal($companies, new CompanyTransformer)
            ->parseIncludes([
                'id',
                'name',
                'status',
                'orders_count',
                'trading_mode',
                'preferred_market_type',
                'unique_name',
                'created_at',
                'order_cost',
            ])
            ->respond();
    }

    public function store(
        StoreCompanyRequest $request,
        CreateCompany $createCompany,
        GetSettingsClassInstance $getSettingsClassInstance
    ): JsonResponse {
        $data = $request->validated();
        $data['order_cost_tiers'] = $this->unsetProrationAmounExceptForLastTier($data['order_cost_tiers']);
        $data['order_cost_tiers'] = $this->castTiersAmountsToMoney($data['order_cost_tiers']);
        $data['type'] = CompanyType::Lender;

        return DB::transaction(function () use ($data, $getSettingsClassInstance, $createCompany) {
            /** @var \App\Settings\Classes\Areas\LenderSettings $lenderSettings */
            $lenderSettings = $getSettingsClassInstance->handle(Area::Lender);
            $data['status'] = $lenderSettings->default_company_status_created_by_operation;

            $lender = $createCompany->handle($data);

            $lender->createWallet(WalletType::CompanyWallet, Money::getDefaultCurrency());

            $lender->tieredPricing()->createMany($data['order_cost_tiers']);

            return fractal($lender, new CompanyTransformer)
                ->parseIncludes([
                    'id',
                    'name',
                    'status',
                    'created_at',
                    'unique_name',
                    'company_cr',
                    'does_order_require_approval',
                    'require_initiate_trade_request',
                    'force_unique_reference_number',
                    'order_cost',
                    'preferred_market_type',
                    'preferred_commodity_types',
                ])
                ->respond();
        });
    }

    public function show(Lender $lender): JsonResponse
    {
        return fractal($lender, new CompanyTransformer)
            ->parseIncludes([
                'id',
                'name',
                'status',
                'created_at',
                'unique_name',
                'company_cr',
                'does_order_require_approval',
                'order_cost_tiers.id',
                'order_cost_tiers.order_value_start',
                'order_cost_tiers.order_value_end',
                'order_cost_tiers.fee_type',
                'order_cost_tiers.order_cost_without_vat',
                'order_cost_tiers.order_cost_with_vat',
                'order_cost_tiers.proration_amount',
                'notifications_email',
                'force_unique_reference_number',
                'require_initiate_trade_request',
                'auto_complete_murabaha_order',
                'trading_mode',
                'contract_number',
                'preferred_market_type',
                'preferred_commodity_types',
                'default_contract_sign_time_limit',
                'allow_preferred_commodity_in_order',
                'lender_order_allowed_commodity_types',
                'allowed_financing_order_types',
            ])
            ->respond();
    }

    public function update(
        UpdateCompanyRequest $request,
        UpdateCompany $updateCompany,
        Lender $lender
    ): JsonResponse {
        return DB::transaction(function () use ($request, $updateCompany, $lender) {
            $data = $request->validated();
            $currency = $lender->getWallet(WalletType::CompanyWallet)->currency;
            $data['order_cost_tiers'] = $this->unsetProrationAmounExceptForLastTier($data['order_cost_tiers']);
            $data['order_cost_tiers'] = $this->castTiersAmountsToMoney($data['order_cost_tiers'], $currency);
            $updateCompany->handle($lender, $data);

            return $this->successResponse();
        });
    }

    public function partialUpdate(
        PartialUpdateCompanyRequest $request,
        UpdateCompany $updateCompany,
        Lender $lender
    ): JsonResponse {
        return DB::transaction(function () use ($request, $updateCompany, $lender) {
            $data = $request->validated();
            $updateCompany->handle($lender, $data);

            return $this->successResponse();
        });
    }

    /**
     * Remove the specified resource from storage.
     *
     *
     * @throws \Throwable
     */
    public function destroy(Lender $lender): JsonResponse
    {
        DB::transaction(function () use ($lender) {
            $lender->update(['unique_name' => null]);
            $lender->delete();
        });

        return $this->successResponse();
    }

    public function unsetProrationAmounExceptForLastTier(array $tiers): array
    {
        $tiersCount = count($tiers);
        for ($i = 0; $i < ($tiersCount - 1); $i++) {
            $tier = &$tiers[$i];
            if (isset($tier['proration_amount'])) {
                $tier['proration_amount'] = null;
            }
        }

        return $tiers;
    }

    protected function castTiersAmountsToMoney($tiers, $currency = null)
    {
        if (is_null($currency)) {
            $currency = Money::getDefaultCurrency();
        }

        foreach ($tiers as &$tier) {
            $orderCostWithVat = Money::parseByDecimal($tier['order_cost_with_vat'], $currency);
            [$vatOfOrderCostAmount] = app(CalculateVatAmount::class)
                ->setAmount($orderCostWithVat)
                ->setIsVatIncludedInAmount(true)
                ->handle();

            $tier['order_cost_without_vat'] = $orderCostWithVat->subtract($vatOfOrderCostAmount);
            $tier['vat_amount'] = $vatOfOrderCostAmount;

            $tier['order_value_start'] = Money::parseByDecimal($tier['order_value_start'], $currency);

            if ($tier['order_value_end'] != null) {
                $tier['order_value_end'] = Money::parseByDecimal($tier['order_value_end'], $currency);
            }

            if (isset($tier['proration_amount']) && $tier['proration_amount'] != null) {
                $tier['proration_amount'] = Money::parseByDecimal($tier['proration_amount'], $currency);
            }
        }

        return $tiers;
    }
}
