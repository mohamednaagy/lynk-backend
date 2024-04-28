<?php

namespace App\Http\Controllers\Api\V1\Admin\Commodities;

use App\Actions\Contracts\Commodities\CommodityType\BuildPaginatedCommodityTypeQuery;
use App\Actions\Contracts\Commodities\CommodityType\CreateCommodityType;
use App\Enums\Action;
use App\Enums\Area;
use App\Enums\Subject;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Admin\Commodities\CommodityType\StoreCommodityTypeRequest;
use App\Transformers\CommodityTypeTransformer;
use Illuminate\Http\JsonResponse;

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

    }

    public function index(
        BuildPaginatedCommodityTypeQuery $buildPaginatedCommodityTypesQuery
    ): JsonResponse {

        $commidityTypes = $buildPaginatedCommodityTypesQuery
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
}
