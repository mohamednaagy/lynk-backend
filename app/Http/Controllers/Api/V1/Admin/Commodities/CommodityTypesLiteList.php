<?php

namespace App\Http\Controllers\Api\V1\Admin\Commodities;

use App\Actions\Contracts\Commodities\CommodityType\BuildPaginatedCommodityTypeQuery;
use App\Enums\Action;
use App\Enums\Area;
use App\Enums\Subject;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Admin\Commodities\CommodityItem\CommodityTypesLiteListRequest;
use App\Transformers\CommodityTypeTransformer;
use Illuminate\Http\JsonResponse;

class CommodityTypesLiteList extends Controller
{
    public function __construct()
    {
        $this->middleware(
            'permission:'.
            perm(Area::SuperAdmin, [Subject::CommodityMarketCommodityTypes, Action::Index, Action::Manage])
        );
    }

    public function __invoke(CommodityTypesLiteListRequest $request, BuildPaginatedCommodityTypeQuery $buildPaginatedCommodityTypeQuery): JsonResponse
    {
        $commidityTypes = $buildPaginatedCommodityTypeQuery
            ->setName($request->validated('search'))
            ->setStatus($request->validated('status'))
            ->handle()
            ->get(['id', 'name']);

        return fractal($commidityTypes, new CommodityTypeTransformer)
            ->parseIncludes(['id', 'name'])
            ->respond();
    }
}
