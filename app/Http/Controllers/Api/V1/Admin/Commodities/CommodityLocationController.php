<?php

namespace App\Http\Controllers\Api\V1\Admin\Commodities;

use App\Actions\Contracts\Commodities\CommodityLocation\CreateSupplierLocation;
use App\Actions\Contracts\Commodities\CommodityLocation\GetPaginatedSupplierLocations;
use App\Actions\Contracts\Commodities\CommodityLocation\UpdateSupplierLocation;
use App\Enums\Action;
use App\Enums\Area;
use App\Enums\Subject;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Admin\Commodities\CommoditySupplier\Locations\StoreLocationRequest;
use App\Http\Requests\V1\Admin\Commodities\CommoditySupplier\Locations\UpdateLocationRequest;
use App\Models\Supplier;
use App\Models\SupplierLocation;
use App\Transformers\SupplierLocationsTransformer;
use Illuminate\Http\JsonResponse;

class CommodityLocationController extends Controller
{
    public function __construct()
    {
        $this->middleware(
            'permission:'.
            perm(Area::SuperAdmin, [Subject::CommoditySupplierLocations, Action::Index, Action::Manage])
        )->only('index');

        $this->middleware(
            'permission:'.
            perm(Area::SuperAdmin, [Subject::CommoditySupplierLocations, Action::Create, Action::Manage])
        )->only('store');
    }

    /**
     * Display a list of supplier locations for a given supplier.
     */
    public function index(Supplier $supplier, GetPaginatedSupplierLocations $getPaginatedSupplierLocations): JsonResponse
    {
        $locations = $getPaginatedSupplierLocations->handle($supplier);

        return fractal($locations, new SupplierLocationsTransformer)
            ->parseIncludes([
                'id',
                'name',
                'unique_identifier',
                'description',
                'created_at',
                'is_deletable',
            ])
            ->respond();
    }

    /**
     * Store a new supplier location.
     */
    public function store(
        StoreLocationRequest $request,
        Supplier $supplier,
        CreateSupplierLocation $createSupplierLocation
    ): JsonResponse {
        $data = $request->validated();
        $data['company_id'] = $supplier->id;

        $location = $createSupplierLocation->handle($data);

        return fractal($location, new SupplierLocationsTransformer)
            ->parseIncludes([
                'id',
                'unique_identifier',
                'name',
                'description',
            ])
            ->respond();
    }

    /**
     * Update an existing supplier location.
     */
    public function update(
        UpdateLocationRequest $request,
        Supplier $supplier,
        SupplierLocation $location,
        UpdateSupplierLocation $updateSupplierLocation
    ): JsonResponse {
        $updatedLocation = $updateSupplierLocation->handle($location, $request->validated());

        return fractal($updatedLocation, new SupplierLocationsTransformer)
            ->parseIncludes([
                'id',
                'unique_identifier',
                'name',
                'description',
            ])
            ->respond();
    }
}
