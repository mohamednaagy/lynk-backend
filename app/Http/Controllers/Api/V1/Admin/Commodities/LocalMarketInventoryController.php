<?php

namespace App\Http\Controllers\Api\V1\Admin\Commodities;

use App\Actions\Contracts\Supplier\CommodityItem\Inventory\GetPaginatedCommodityInventories;
use App\Enums\Action;
use App\Enums\Area;
use App\Enums\Subject;
use App\Http\Controllers\Controller;
use App\Models\CommodityItem;
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
    }

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
}
