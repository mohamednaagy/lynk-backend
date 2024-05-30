<?php

namespace App\Http\Controllers\Api\V1\Supplier\CommodityItem;

use App\Actions\Contracts\Supplier\CommodityItem\BuildPaginatedCommodityItemQuery;
use App\Actions\Contracts\Supplier\CommodityItem\CreateCommodityItem;
use App\Actions\Contracts\Supplier\CommodityItem\UpdateCommodityItem;
use App\Enums\Action;
use App\Enums\Area;
use App\Enums\Subject;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Supplier\CommodityItem\StoreCommodityItemRequest;
use App\Http\Requests\V1\Supplier\CommodityItem\UpdateCommodityItemRequest;
use App\Models\CommodityItem;
use App\Transformers\Supplier\CommodityItem\CommodityItemsTransformer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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
        )
            ->only('store');
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
}
