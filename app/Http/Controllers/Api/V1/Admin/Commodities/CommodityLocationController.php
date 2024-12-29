<?php

namespace App\Http\Controllers\Api\V1\Admin\Commodities;

use App\Actions\Contracts\Commodities\CommodityLocation\CreateSupplierLocation;
use App\Actions\Contracts\Commodities\CommodityLocation\GetPaginatedSupplierLocations;
use App\Enums\Action;
use App\Enums\Area;
use App\Enums\Subject;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Admin\Commodities\CommoditySupplier\Locations\StoreLocationRequest;
use App\Models\Supplier;
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
     * Display a listing of the resource.
     */
    public function index(
        Supplier $supplier,
        GetPaginatedSupplierLocations $getPaginatedSupplierLocations
    ): JsonResponse {
        $enquiries = $getPaginatedSupplierLocations->handle($supplier);

        return fractal($enquiries, new SupplierLocationsTransformer)
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
     * Store a newly created resource in storage.
     */
    public function store(StoreLocationRequest $storeLocationRequest, Supplier $supplier,
        CreateSupplierLocation $createSupplierLocation): JsonResponse
    {
        $data = $storeLocationRequest->validated();
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
}
