<?php

namespace App\Http\Controllers\Api\V1\Admin\Commodities;

use App\Actions\Contracts\Supplier\CommodityItem\Inventory\CreateLocalMarketInventory;
use App\Actions\Contracts\Supplier\CommodityItem\Inventory\GetPaginatedCommodityInventories;
use App\Actions\Contracts\Supplier\CommodityItem\Inventory\UpdateCommodityInventory;
use App\Enums\Action;
use App\Enums\Area;
use App\Enums\LocalMarket\InventoryStatus;
use App\Enums\Subject;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Admin\Commodities\CommodityInventory\StoreLocalMarketInventoryRequest;
use App\Http\Requests\V1\Supplier\Inventories\UpdateLocalMarketInventoryRequest;
use App\Models\CommodityItem;
use App\Models\LocalMarketInventory;
use App\Transformers\LocalMarketInventoryTransformer;
use Illuminate\Http\JsonResponse;

class LocalMarketInventoryController extends Controller
{
    public function __construct()
    {
        $this->middleware(
            'permission:'.
                perm(Area::SuperAdmin, [Subject::CommoditySupplierInventories, Action::Manage, Action::Index])
        )->only('index');

        $this->middleware(
            'permission:'.
                perm(Area::SuperAdmin, [Subject::CommoditySupplierInventories, Action::Manage, Action::Create])
        )->only('store');

        $this->middleware(
            'permission:'.
                perm(Area::SuperAdmin, [Subject::CommoditySupplierInventories, Action::Manage, Action::Show])
        )->only('show');

        $this->middleware(
            'permission:'.
                perm(Area::SuperAdmin, [Subject::CommoditySupplierInventories, Action::Manage, Action::Edit])
        )->only('update');
    }

    /**
     * Display a paginated list of commodity inventories for a given commodity item.
     *
     * This method retrieves the paginated inventory data for the specified
     * commodity item and transforms it using the LocalMarketInventoryTransformer.
     * The response includes various attributes such as company details, pricing,
     * supplier location, and inventory status.
     *
     * @param  CommodityItem  $item  The commodity item for which inventories are retrieved.
     * @param  GetPaginatedCommodityInventories  $getPaginatedCommodityInventory  The action to get paginated inventories.
     * @return JsonResponse The JSON response containing the transformed inventory data.
     */
    public function index(
        CommodityItem $item,
        GetPaginatedCommodityInventories $getPaginatedCommodityInventory
    ): JsonResponse {

        $data = $getPaginatedCommodityInventory->handle($item->supplier, $item);

        return fractal($data, new LocalMarketInventoryTransformer)
            ->parseIncludes([
                'id',
                'company_id',
                'company_name',
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
     * Store a new commodity inventory in storage.
     *
     * This method stores a new commodity inventory in the database and returns
     * a JSON response containing the newly created inventory data.
     *
     * @param  CommodityItem  $item  The commodity item for which the inventory is stored.
     * @param  StoreLocalMarketInventoryRequest  $storeInventoryRequest  The request containing the inventory data.
     * @param  CreateLocalMarketInventory  $createSupplierInventory  The action to create the inventory.
     * @return JsonResponse The JSON response containing the newly created inventory data.
     */
    public function store(CommodityItem $item, StoreLocalMarketInventoryRequest $storeInventoryRequest, CreateLocalMarketInventory $createSupplierInventory): JsonResponse
    {
        $data = $storeInventoryRequest->validated();
        $data['company_id'] = $item->company_id;
        $data['status'] = InventoryStatus::Active;
        $createSupplierInventory->setSupplier($item->supplier);
        $createSupplierInventory->setItem($item);
        $inventory = $createSupplierInventory->handle($data);

        return fractal($inventory, new LocalMarketInventoryTransformer)
            ->parseIncludes([
                'id',
                'company_id',
                'company_name',
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

    public function show(CommodityItem $item, LocalMarketInventory $inventory): JsonResponse
    {
        return fractal($inventory, new LocalMarketInventoryTransformer)
            ->parseIncludes([
                'id',
                'company_id',
                'company_name',
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

    public function update(CommodityItem $item, LocalMarketInventory $inventory, UpdateLocalMarketInventoryRequest $updateInventoryRequest, UpdateCommodityInventory $updateCommodityInventory)
    {
        try {
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
        } catch (\Throwable $th) {
            throw $th;
        }
    }
}
