<?php

namespace App\Http\Controllers\Api\V1\Supplier\CommodityType;

use App\Actions\Contracts\Commodities\CommodityType\BuildPaginatedCommodityTypeQuery;
use App\Enums\Action;
use App\Enums\Area;
use App\Enums\Subject;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Supplier\CommodityType\CommodityTypesLiteListRequest;
use App\Transformers\CommodityTypeTransformer;
use Illuminate\Http\JsonResponse;

class CommodityTypesLiteList extends Controller
{
    public function __construct()
    {
        $this->middleware(
            'permission:'.
                perm(Area::CommoditySupplier, [Subject::CommodityMarketCommodityTypes, Action::Index, Action::Manage])
        );
    }

    /**
     * Handle the incoming request to list commodity types.
     */
    public function __invoke(CommodityTypesLiteListRequest $request, BuildPaginatedCommodityTypeQuery $buildPaginatedCommodityTypeQuery): JsonResponse
    {
        $commidityTypes = $buildPaginatedCommodityTypeQuery
            ->setName($request->validated('search'))
            ->setStatus($request->validated('status'))
            ->setProviders($request->validated('provider'))
            ->handle()
            ->get(['id', 'name', 'provider']);

        return fractal($commidityTypes, new CommodityTypeTransformer)
            ->parseIncludes(['id', 'name', 'provider'])
            ->respond();
    }
}
