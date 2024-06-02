<?php

namespace App\Http\Controllers\Api\V1\Admin\Commodities;

use App\Actions\Contracts\Commodities\CommodityType\BuildPaginatedCommodityTypeQuery;
use App\Actions\Contracts\Commodities\CommodityType\CreateCommodityType;
use App\Actions\Contracts\Commodities\CommodityType\UpdateCommodityType;
use App\Enums\Action;
use App\Enums\Area;
use App\Enums\Subject;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Admin\Commodities\CommodityType\ListCommodityTypeRequest;
use App\Http\Requests\V1\Admin\Commodities\CommodityType\StoreCommodityTypeRequest;
use App\Http\Requests\V1\Admin\Commodities\CommodityType\UpdateCommodityTypeRequest;
use App\Models\CommodityType;
use App\Transformers\CommodityTypeTransformer;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class CommodityTypeController extends Controller
{
    public function __construct()
    {
        $this->middleware(
            'permission:'.
            perm(Area::SuperAdmin, [Subject::CommodityMarketCommodityTypes, Action::Index, Action::Manage])
        )->only('index');

        $this->middleware(
            'permission:'.
            perm(Area::SuperAdmin, [Subject::CommodityMarketCommodityTypes, Action::Create, Action::Manage])
        )->only('store');

        $this->middleware(
            'permission:'.
            perm(Area::SuperAdmin, [Subject::CommodityMarketCommodityTypes, Action::Show, Action::Manage])
        )->only('show');

        $this->middleware(
            'permission:'.
            perm(Area::SuperAdmin, [Subject::CommodityMarketCommodityTypes, Action::Edit, Action::Manage])
        )->only('update');

    }

    public function index(
        ListCommodityTypeRequest $request,
        BuildPaginatedCommodityTypeQuery $buildPaginatedCommodityTypesQuery
    ): JsonResponse {

        $commidityTypes = $buildPaginatedCommodityTypesQuery
            ->setStatus($request->status)
            ->handle();

        return fractal($commidityTypes, new CommodityTypeTransformer())
            ->parseIncludes([
                'id',
                'name',
                'unique_name',
                'description',
                'status',
                'created_at',
            ])
            ->respond();
    }

    public function store(
        StoreCommodityTypeRequest $storeCommodityTypeRequest,
        CreateCommodityType $createCommodityType
    ): JsonResponse {
        $data = $storeCommodityTypeRequest->validated();
        $createCommodityType = $createCommodityType->handle($data);

        return fractal($createCommodityType, new CommodityTypeTransformer())
            ->parseIncludes([
                'id',
                'name',
                'description',
                'unique_name',
                'status',
            ])
            ->respond();
    }

    public function show(CommodityType $commodityType): JsonResponse
    {
        return fractal($commodityType, new CommodityTypeTransformer())
            ->parseIncludes([
                'id',
                'name',
                'description',
                'unique_name',
                'status',
            ])
            ->respond();
    }

    public function update(
        UpdateCommodityTypeRequest $request,
        UpdateCommodityType $updateCommodityType,
        CommodityType $commodityType
    ): JsonResponse {
        return DB::transaction(function () use ($request, $updateCommodityType, $commodityType) {
            $data = $request->validated();
            $updateCommodityType->handle($commodityType, $data);

            return $this->successResponse();
        });
    }
}
