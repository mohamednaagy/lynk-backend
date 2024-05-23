<?php

namespace App\Http\Controllers\Api\V1\Supplier\CommodityType;

use App\Actions\Contracts\Commodities\CommodityType\BuildPaginatedCommodityTypeQuery;
use App\Enums\Action;
use App\Enums\Area;
use App\Enums\Subject;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Supplier\CommodityType\ListCommodityTypeRequest;
use App\Transformers\CommodityTypeTransformer;
use Illuminate\Http\JsonResponse;

class CommodityTypeController extends Controller
{
    public function __construct()
    {
        $this->middleware(
            'permission:'.
                perm(Area::CommoditySupplier, [Subject::CommodityMarketCommodityTypes, Action::Index, Action::Manage])
        )->only('index');

    }

    /**
     * Display a listing of the resource.
     *
     * @return JsonResponse
     */
    public function index(ListCommodityTypeRequest $request, BuildPaginatedCommodityTypeQuery $buildPaginatedCommodityTypesQuery)
    {
        $commidityTypes = $buildPaginatedCommodityTypesQuery
            ->setStatus($request->status)
            ->handle();

        return fractal($commidityTypes, new CommodityTypeTransformer())
            ->parseIncludes([
                'id',
                'name',
            ])
            ->respond();

    }
}
