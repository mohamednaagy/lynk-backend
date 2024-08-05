<?php

namespace App\Http\Controllers\Api\V1\Supplier\CommodityItem;

use App\Actions\Contracts\Supplier\CommodityItem\BuildPaginatedCommodityItemQuery;
use App\Actions\Contracts\Supplier\CommodityItem\CreateCommodityItem;
use App\Actions\Contracts\Supplier\CommodityItem\DeleteCommodityItem;
use App\Actions\Contracts\Supplier\CommodityItem\UpdateCommodityItem;
use App\Enums\Action;
use App\Enums\Area;
use App\Enums\ErrorCode;
use App\Enums\Subject;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Supplier\CommodityItem\StoreCommodityItemRequest;
use App\Http\Requests\V1\Supplier\CommodityItem\UpdateCommodityItemRequest;
use App\Models\CommodityItem;
use App\Transformers\Supplier\CommodityItem\CommodityItemsTransformer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class CommodityItemController extends Controller
{
    public function __construct()
    {
        $this->middleware(
            'permission:'.
            perm(Area::CommoditySupplier, [Subject::CommoditySupplierItems, Action::Index, Action::Manage])
        )->only('index');

        $this->middleware(
            'permission:'.
                perm(Area::CommoditySupplier, [Subject::CommoditySupplierItems, Action::Manage, Action::Create])
        )
            ->only('store');

        $this->middleware(
            'permission:'.
            perm(Area::CommoditySupplier, [Subject::CommoditySupplierItems, Action::Show, Action::Manage])
        )->only('show');

        $this->middleware(
            'permission:'.
            perm(Area::CommoditySupplier, [Subject::CommoditySupplierItems, Action::Manage, Action::Edit])
        )->only('update');
        
        $this->middleware(
            'permission:' .
                perm(Area::CommoditySupplier, [Subject::CommoditySupplierItems, Action::Manage, Action::Delete])
        )->only('destroy');
    }

    /**
     * Display a listing of the resource.
     */
    public function index(
        Request $request,
        BuildPaginatedCommodityItemQuery $getPaginatedItems
    ): JsonResponse {
        $items = $getPaginatedItems->handle(tenant()->supplier);

        return fractal($items, new CommodityItemsTransformer())
            ->parseIncludes([
                'id',
                'name',
                'unique_name',
                'commodity_type',
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
        $item = $createCommodityItem->setSupplier(tenant())->handle($data);

        return fractal($item, new CommodityItemsTransformer())
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
     * Display the specified resource.
     */
    public function show(CommodityItem $commodityItem): JsonResponse
    {

        return fractal($commodityItem, new CommodityItemsTransformer())
            ->parseIncludes([
                'id',
                'name',
                'unique_name',
                'commodity_type',
                'description',
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
     * Update the specified resource in storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateCommodityItemRequest $request, CommodityItem $commodityItem, UpdateCommodityItem $updateItem): JsonResponse
    {

        $item = $updateItem->handle($commodityItem, $request->validated());

        return fractal($item, new CommodityItemsTransformer())
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
     * Delete the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(CommodityItem $commodityItem, DeleteCommodityItem $deleteCommodityItem)
    {
        //check if the commodity item is deleteable
        if (!$commodityItem->is_deletable) {
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
