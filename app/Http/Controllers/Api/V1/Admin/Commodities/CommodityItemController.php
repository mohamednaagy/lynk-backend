<?php

namespace App\Http\Controllers\Api\V1\Admin\Commodities;

use App\Actions\Contracts\Commodities\CommodityItem\CreateCommodityItem;
use App\Actions\Contracts\Commodities\CommodityItem\DeleteCommodityItem;
use App\Actions\Contracts\Commodities\CommodityItem\GetPaginatedCommodityItems;
use App\Actions\Contracts\Commodities\CommodityItem\UpdateCommodityItem;
use App\Enums\Action;
use App\Enums\Area;
use App\Enums\ErrorCode;
use App\Enums\Subject;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Admin\Commodities\CommodityItem\ListCommodityItemsRequest;
use App\Http\Requests\V1\Admin\Commodities\CommodityItem\StoreCommodityItemRequest;
use App\Http\Requests\V1\Admin\CommodityItem\UpdateCommodityItemRequest;
use App\Models\CommodityItem;
use App\Transformers\Admin\CommodityItem\CommodityItemsTransformer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class CommodityItemController extends Controller
{
    public function __construct()
    {
        $this->middleware(
            'permission:'.
                perm(Area::SuperAdmin, [Subject::CommodityMarketCommodityItems, Action::Index, Action::Manage])
        )->only('index', 'show');

        $this->middleware(
            'permission:'.
            perm(Area::SuperAdmin, [Subject::CommodityMarketCommodityItems, Action::Manage, Action::Edit])
        )->only('update');

        $this->middleware(
            'permission:'.
            perm(Area::SuperAdmin, [Subject::CommodityMarketCommodityItems, Action::Manage, Action::Delete])
        )->only('destroy');

    }

    /**
     * Get a paginated list of commodity items.
     */
    public function index(
        ListCommodityItemsRequest $request,
        GetPaginatedCommodityItems $getPaginatedCommodityItems
    ): JsonResponse {
        $commidityItems = $getPaginatedCommodityItems
            ->setName($request->validated('name'))
            ->setuniqueName($request->validated('unique_name'))
            ->setCommodityTypes($request->validated('commodity_types'))
            ->setSuppliers($request->validated('suppliers'))
            ->setSort($request->validated('sort'))
            ->setActive($request->validated('active'))
            ->setDirection($request->validated('direction'))
            ->handle();

        return fractal($commidityItems, new CommodityItemsTransformer)
            ->parseIncludes([
                'id',
                'name',
                'supplier',
                'unique_name',
                'company_id',
                'commodity_type',
                'max_price',
                'available_units',
                'reserved_units',
                'is_deletable',
            ])
            ->respond();
    }

    /**
     * Display the specified resource.
     */
    public function show(CommodityItem $commodityItem): JsonResponse
    {

        return fractal($commodityItem, new CommodityItemsTransformer)
            ->parseIncludes([
                'id',
                'name',
                'unique_name',
                'commodity_type',
                'description',
                'supplier',
                'min_price',
                'max_price',
                'volume_sellable_unit',
                'currency',
                'measurement',
                'available_units',
                'reserved_units',
                'created_at',
                'is_deletable',
            ])
            ->respond();
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreCommodityItemRequest $storeNewItem, CreateCommodityItem $createCommodityItem): JsonResponse
    {
        $data = $storeNewItem->validated();
        $item = $createCommodityItem->handle($data);

        return fractal($item, new CommodityItemsTransformer)
            ->parseIncludes([
                'id',
                'name',
                'unique_name',
                'commodity_type',
                'created_at',
            ])
            ->respond();
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateCommodityItemRequest $request, CommodityItem $commodityItem, UpdateCommodityItem $updateItem): JsonResponse
    {
        $item = $updateItem->handle($commodityItem, $request->validated());

        return fractal($item, new CommodityItemsTransformer)
            ->parseIncludes([
                'id',
                'supplier',
                'name',
                'unique_name',
                'commodity_type',
                'created_at',
            ])
            ->respond();
    }

    /**
     * Delete the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(CommodityItem $commodityItem, DeleteCommodityItem $deleteCommodityItem)
    {
        //check if the commodity item is deleteable
        if (! $commodityItem->is_deletable) {
            return $this->errorResponse(
                __('error.commodity_item_cannot_be_deleted'),
                Response::HTTP_BAD_REQUEST,
                ErrorCode::COMMODITY_ITEM_NOT_DELETABLE
            );
        }

        try {
            $deleteCommodityItem->handle($commodityItem);

            return $this->successResponse();
        } catch (\Exception $e) {
            Log::error("Failed to delete commodity item ID: {$commodityItem->id}. Error: {$e->getMessage()}");

            return $this->errorResponse(
                __('error.failed_to_delete_commodity_item'),
                Response::HTTP_UNPROCESSABLE_ENTITY,
                ErrorCode::FAILED_TO_DELETE_COMMODITY_ITEM
            );
        }
    }
}
