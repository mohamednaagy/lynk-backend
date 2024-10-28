<?php
namespace App\Http\Controllers\Api\V1\Admin\Commodities;

use App\Actions\Contracts\Commodities\CommodityItem\GetPaginatedCommodityItems;
use App\Enums\Action;
use App\Enums\Area;
use App\Enums\Subject;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Admin\Commodities\CommodityItem\ListCommodityItemsRequest;
use App\Transformers\Admin\CommodityItem\CommodityItemsTransformer;
use Illuminate\Http\JsonResponse;

class CommodityItemController extends Controller
{
    public function __construct()
    {
        $this->middleware(
            'permission:' .
            perm(Area::SuperAdmin, [Subject::CommodityMarketCommodityItems, Action::Index, Action::Manage])
        )->only('index');

    }

    /**
     * Get a paginated list of commodity items.
     *
     * @param ListCommodityItemsRequest $request
     * @param GetPaginatedCommodityItems $getPaginatedCommodityItems
     * @return JsonResponse
     */
    public function index(
        ListCommodityItemsRequest $request,
        GetPaginatedCommodityItems $getPaginatedCommodityItems
    ): JsonResponse {
        $commidityItems = $getPaginatedCommodityItems
            ->setName($request->validated('name'))
            ->setuniqueName($request->validated('unique_name'))
            ->setCommodityTypes(
                $request->validated('commodity_type')
                ? collect($request->validated('commodity_type'))->pluck('id')->toArray()
                : []
            )
            ->setSuppliers($request->validated('supplier')
                ? collect($request->validated('supplier'))->pluck('id')->toArray()
                : [])
            ->setSort($request->validated('sort'))
            ->setDirection($request->validated('direction'))
            ->handle();


        return fractal($commidityItems, new CommodityItemsTransformer())
            ->parseIncludes([
                'id',
                'name',
                'supplier',
                'unique_name',
                'company_id',
                'commodity_type',
                'available_units',
                'reserved_units',
            ])
            ->respond();
    }
}
