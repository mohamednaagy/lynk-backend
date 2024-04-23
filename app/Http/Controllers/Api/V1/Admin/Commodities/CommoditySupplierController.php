<?php

namespace App\Http\Controllers\Api\V1\Admin\Commodities;

use App\Actions\Contracts\Commodities\CommoditySupplier\BuildPaginatedCommoditySuppliersQuery;
use App\Actions\Contracts\Commodities\CommoditySupplier\CreateCommoditySupplier;
use App\Enums\Action;
use App\Enums\Area;
use App\Enums\Subject;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Admin\Commodities\StoreCommoditySupplierRequest;
use App\Transformers\CommoditySuppliersTransformer;
use Illuminate\Http\JsonResponse;

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

    }

    public function index(
        BuildPaginatedCommoditySuppliersQuery $buildPaginatedCommoditySuppliersQuery
    ): JsonResponse {

        $commiditySuppliers = $buildPaginatedCommoditySuppliersQuery
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
}
