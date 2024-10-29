<?php

namespace App\Http\Controllers\Api\V1\Admin\Commodities;

use App\Actions\Contracts\Commodities\CommodityType\BuildPaginatedCommodityTypeQuery;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use App\Transformers\CommodityTypeTransformer;
use App\Enums\Area;
use App\Enums\Action;
use App\Enums\Subject;

class CommodityTypesLiteList extends Controller
{

    public function __construct()
    {
        $this->middleware(
            'permission:' .
            perm(Area::SuperAdmin, [Subject::CommodityMarketCommodityTypes, Action::Index, Action::Manage])
        );
    }


    /**
     * Handle the incoming request to list commodity types.
     *
     * @param BuildPaginatedCommodityTypeQuery $buildPaginatedCommodityTypeQuery
     * @return JsonResponse
     */
    public function __invoke(BuildPaginatedCommodityTypeQuery $buildPaginatedCommodityTypeQuery): JsonResponse
    {
        $commidityTypes = $buildPaginatedCommodityTypeQuery
            ->setName(request('search'))
            ->handle()
            ->get(['id', 'name']);

        return fractal($commidityTypes, new CommodityTypeTransformer())
            ->parseIncludes(['id', 'name'])
            ->respond();
    }
}
