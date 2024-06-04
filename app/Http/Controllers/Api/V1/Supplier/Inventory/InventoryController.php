<?php

namespace App\Http\Controllers\Api\V1\Supplier\Inventory;

use App\Actions\Contracts\Supplier\CommodityItem\Inventory\CreateCommodityInventory;
use App\Actions\Contracts\Supplier\CommodityItem\Inventory\GetPaginatedCommodityInventories;
use App\Enums\Action;
use App\Enums\Area;
use App\Enums\Subject;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Supplier\Inventories\StoreInventoryRequest;
use App\Models\CommodityItem;
use App\Transformers\InventoryTransformer;
use Illuminate\Http\JsonResponse;

class InventoryController extends Controller
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

        return fractal($data, new InventoryTransformer())
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
            ])
            ->respond();
    }


    /**
     * Store a newly created resource in storage.
     */
    public function store(CommodityItem $item, StoreInventoryRequest $storeInventoryRequest, CreateCommodityInventory $createSupplierInventory): JsonResponse
    {
        $data = $storeInventoryRequest->validated();
        $data['company_id'] = tenant()->id;
        $createSupplierInventory->setSupplier(tenant());
        $createSupplierInventory->setItem($item);
        $inventory = $createSupplierInventory->handle($data);
        

        return fractal($inventory, new InventoryTransformer())
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

    
}
