<?php

namespace App\Http\Controllers\Api\V1\Admin\Commodities;

use App\Actions\Contracts\Commodities\CommoditySupplier\BuildPaginatedCommoditySuppliersQuery;
use App\Actions\Contracts\Commodities\CommoditySupplier\CreateCommoditySupplier;
use App\Actions\Contracts\Commodities\CommoditySupplier\UpdateCommoditySupplier;
use App\Enums\Action;
use App\Enums\Area;
use App\Enums\CompanyType;
use App\Enums\Subject;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Admin\Commodities\CommoditySupplier\StoreCommoditySupplierRequest;
use App\Http\Requests\V1\Admin\Commodities\CommoditySupplier\UpdateCommoditySupplierRequest;
use App\Models\Company;
use App\Transformers\CommoditySuppliersTransformer;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class CommoditySupplierController extends Controller
{
    public function __construct()
    {
        $this->middleware(
            'permission:'.
            perm(Area::SuperAdmin, [Subject::CommodityMarketSuppliers, Action::Index, Action::Manage])
        )->only('index');

        $this->middleware(
            'permission:'.
            perm(Area::SuperAdmin, [Subject::CommodityMarketSuppliers, Action::Create, Action::Manage])
        )->only('store');

        $this->middleware(
            'permission:'.
            perm(Area::SuperAdmin, [Subject::CommodityMarketSuppliers, Action::Show, Action::Manage])
        )->only('show');

        $this->middleware(
            'permission:'.
            perm(Area::SuperAdmin, [Subject::CommodityMarketSuppliers, Action::Edit, Action::Manage])
        )->only('update');
    }

    public function index(
        BuildPaginatedCommoditySuppliersQuery $buildPaginatedCommoditySuppliersQuery
    ): JsonResponse {

        $commiditySuppliers = $buildPaginatedCommoditySuppliersQuery->setType(CompanyType::Supplier)
            ->handle()
            ->paginate();

        return fractal($commiditySuppliers, new CommoditySuppliersTransformer())
            ->parseIncludes([
                'id',
                'legal_name',
                'unique_name',
                'market_type',
                'status',
                'created_at',
            ])
            ->respond();
    }

    public function store(
        StoreCommoditySupplierRequest $storeCommoditySupplierRequest,
        CreateCommoditySupplier $createCommoditySupplier
    ): JsonResponse {
        $data = $storeCommoditySupplierRequest->validated();
        $createCommoditySupplier = $createCommoditySupplier->handle($data);

        return fractal($createCommoditySupplier, new CommoditySuppliersTransformer())
            ->parseIncludes([
                'id',
                'legal_name',
                'description',
                'unique_name',
                'market_type',
                'status',
            ])
            ->respond();
    }

    public function show(Company $commoditySupplier): JsonResponse
    {

        return fractal($commoditySupplier, new CommoditySuppliersTransformer())
            ->parseIncludes([
                'id',
                'legal_name',
                'description',
                'unique_name',
                'market_type',
                'status',
            ])
            ->respond();
    }

    public function update(
        UpdateCommoditySupplierRequest $request,
        UpdateCommoditySupplier $updateCommoditySupplier,
        Company $commoditySupplier
    ): JsonResponse {
        return DB::transaction(function () use ($request, $updateCommoditySupplier, $commoditySupplier) {
            $data = $request->validated();
            $updateCommoditySupplier->handle($commoditySupplier, $data);

            return $this->successResponse();
        });
    }
}
