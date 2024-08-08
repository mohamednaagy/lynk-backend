<?php

namespace App\Http\Controllers\Api\V1\Supplier\Inventory;

use App\Actions\Contracts\Supplier\CommodityItem\Inventory\CreateLocalMarketInventory;
use App\Actions\Contracts\Supplier\CommodityItem\Inventory\DeleteCommodityInventory;
use App\Actions\Contracts\Supplier\CommodityItem\Inventory\GetPaginatedCommodityInventories;
use App\Actions\Contracts\Supplier\CommodityItem\Inventory\UpdateCommodityInventory;
use App\Enums\Action;
use App\Enums\Area;
use App\Enums\ErrorCode;
use App\Enums\Subject;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Supplier\Inventories\StoreLocalMarketInventoryRequest;
use App\Http\Requests\V1\Supplier\Inventories\UpdateLocalMarketInventoryRequest;
use App\Models\CommodityItem;
use App\Models\LocalMarketInventory;
use App\Transformers\LocalMarketInventoryTransformer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class LocalMarketInventoryController extends Controller
{
    public function __construct()
    {
        $this->middleware(
            'permission:'.
                perm(Area::CommoditySupplier, [Subject::CommoditySupplierInventories, Action::Manage, Action::Index])
        )
            ->only('index');

        $this->middleware(
            'permission:'.
                perm(Area::CommoditySupplier, [Subject::CommoditySupplierInventories, Action::Manage, Action::Create])
        )
            ->only('store');

        $this->middleware(
            'permission:'.
                perm(Area::CommoditySupplier, [Subject::CommoditySupplierInventories, Action::Manage, Action::Edit])
        )->only('update');

        $this->middleware(
            'permission:'.
                perm(Area::CommoditySupplier, [Subject::CommoditySupplierInventories, Action::Manage, Action::Show])
        )->only('show');

        $this->middleware(
            'permission:'.
                perm(Area::CommoditySupplier, [Subject::CommoditySupplierInventories, Action::Manage, Action::Delete])
        )->only('destroy');
    }

    /**
     * Display a listing of the resource.
     */
    public function index(
        CommodityItem $item,
        GetPaginatedCommodityInventories $getPaginatedCommodityInventory
    ): JsonResponse {
        $supplier = tenant()->supplier;
        $data = $getPaginatedCommodityInventory->handle($supplier, $item);

        return fractal($data, new LocalMarketInventoryTransformer)
            ->parseIncludes([
                'id',
                'company_id',
                'comapny_name',
                'commodity_item_id',
                'commodity_item',
                'commodity_type',
                'min_price',
                'max_price',
                'supplier_location_id',
                'supplier_location',
                'total_items',
                'available_quantity',
                'reserved_items',
                'status',
                'is_editable',
                'is_deletable',
            ])
            ->respond();
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(CommodityItem $item, StoreLocalMarketInventoryRequest $storeInventoryRequest, CreateLocalMarketInventory $createSupplierInventory): JsonResponse
    {
        $data = $storeInventoryRequest->validated();
        $data['company_id'] = tenant()->id;
        $createSupplierInventory->setSupplier(tenant());
        $createSupplierInventory->setItem($item);
        $inventory = $createSupplierInventory->handle($data);

        return fractal($inventory, new LocalMarketInventoryTransformer)
            ->parseIncludes([
                'id',
                'company_id',
                'comapny_name',
                'commodity_item_id',
                'commodity_item',
                'commodity_type',
                'min_price',
                'max_price',
                'supplier_location_id',
                'supplier_location',
                'total_items',
                'available_quantity',
                'reserved_items',
                'status',
            ])
            ->respond();
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(CommodityItem $item, LocalMarketInventory $inventory, UpdateLocalMarketInventoryRequest $updateInventoryRequest, UpdateCommodityInventory $updateCommodityInventory)
    {
        //double check if the inventory is editable
        if (! $inventory->canUpdateUnits($updateInventoryRequest->total_units)) {
            return $this->errorResponse(
                __('error.inventory_cannot_be_updated'),
                Response::HTTP_BAD_REQUEST,
                ErrorCode::INVENTORY_NOT_UPDATABLE
            );
        }

        $inventory = $updateCommodityInventory->handle($inventory, $updateInventoryRequest->validated());

        return fractal($inventory, new LocalMarketInventoryTransformer)
            ->parseIncludes([
                'id',
                'company_id',
                'comapny_name',
                'commodity_item_id',
                'commodity_item',
                'commodity_type',
                'min_price',
                'max_price',
                'supplier_location_id',
                'supplier_location',
                'total_items',
                'available_quantity',
                'reserved_items',
                'status',
            ])
            ->respond();
    }

    /**
     * Display the specified resource.
     */
    public function show(CommodityItem $item, LocalMarketInventory $inventory): JsonResponse
    {
        return fractal($inventory, new LocalMarketInventoryTransformer)
            ->parseIncludes([
                'id',
                'company_id',
                'comapny_name',
                'commodity_item_id',
                'commodity_item',
                'commodity_type',
                'min_price',
                'max_price',
                'supplier_location_id',
                'supplier_location',
                'total_items',
                'available_quantity',
                'reserved_items',
                'status',
                'is_editable',
                'is_deletable',
            ])
            ->respond();
    }

    /**
     * Delete the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(CommodityItem $item, LocalMarketInventory $inventory, DeleteCommodityInventory $deleteCommodityInventory)
    {
        //check if the inventory is deleteable
        if (! $inventory->is_deletable) {
            return $this->errorResponse(
                __('error.inventory_cannot_be_deleted'),
                Response::HTTP_BAD_REQUEST,
                ErrorCode::INVENTORY_NOT_DELETABLE
            );
        }

        try {
            $deleteCommodityInventory->handle($inventory);

            return $this->successResponse();
        } catch (\Exception $e) {
            Log::error("Failed to delete inventory ID: {$inventory->id}. Error: {$e->getMessage()}");

            return $this->errorResponse(
                __('error.failed_to_delete_inventory'),
                Response::HTTP_UNPROCESSABLE_ENTITY,
                ErrorCode::FAILED_TO_DELETE_INVENTORY
            );
        }
    }
}
