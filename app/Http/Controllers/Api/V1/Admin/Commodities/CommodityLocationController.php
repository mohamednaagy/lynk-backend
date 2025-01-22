<?php

namespace App\Http\Controllers\Api\V1\Admin\Commodities;

use App\Actions\Contracts\Commodities\CommodityLocation\CreateSupplierLocation;
use App\Actions\Contracts\Commodities\CommodityLocation\DeleteSupplierLocation;
use App\Actions\Contracts\Commodities\CommodityLocation\GetPaginatedSupplierLocations;
use App\Actions\Contracts\Commodities\CommodityLocation\UpdateSupplierLocation;
use App\Enums\Action;
use App\Enums\Area;
use App\Enums\ErrorCode;
use App\Enums\Subject;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Admin\Commodities\CommoditySupplier\Locations\StoreLocationRequest;
use App\Http\Requests\V1\Admin\Commodities\CommoditySupplier\Locations\UpdateLocationRequest;
use App\Models\Supplier;
use App\Models\SupplierLocation;
use App\Transformers\SupplierLocationsTransformer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class CommodityLocationController extends Controller
{
    public function __construct()
    {
        $this->middleware(
            'permission:'.
            perm(Area::SuperAdmin, [Subject::CommoditySupplierLocations, Action::Manage, Action::Index])
        )->only('index');

        $this->middleware(
            'permission:'.
            perm(Area::SuperAdmin, [Subject::CommoditySupplierLocations, Action::Manage, Action::Create])
        )->only('store');

        $this->middleware(
            'permission:'.
            perm(Area::SuperAdmin, [Subject::CommoditySupplierLocations, Action::Manage, Action::Edit])
        )->only('update');

        $this->middleware(
            'permission:'.
            perm(Area::SuperAdmin, [Subject::CommoditySupplierLocations, Action::Manage, Action::Show])
        )->only('show');

        $this->middleware(
            'permission:'.
            perm(Area::SuperAdmin, [Subject::CommoditySupplierLocations, Action::Manage, Action::Delete])
        )->only('destroy');
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

    /**
     * Display the specified supplier location.
     */
    public function show(Supplier $supplier, SupplierLocation $location): JsonResponse
    {
        return fractal($location, new SupplierLocationsTransformer)
            ->parseIncludes([
                'id',
                'unique_identifier',
                'name',
                'description',
                'created_at',
                'is_deletable',
            ])
            ->respond();
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(
        Supplier $supplier,
        SupplierLocation $location,
        DeleteSupplierLocation $deleteSupplierLocation
    ) {
        // Check if the commodity location is deletable
        if (! $location->is_deletable) {
            return $this->errorResponse(
                __('error.location_cannot_be_deleted'),
                Response::HTTP_BAD_REQUEST,
                ErrorCode::LOCATION_NOT_DELETABLE
            );
        }

        try {
            // Attempt to delete the location
            $deleteSupplierLocation->handle($location);

            return $this->successResponse();
        } catch (\Throwable $exception) {
            // Log the error for debugging
            Log::error(sprintf(
                'Failed to delete location with ID: %d. Error: %s',
                $location->id,
                $exception->getMessage()
            ));

            return $this->errorResponse(
                __('error.failed_to_delete_location'),
                Response::HTTP_UNPROCESSABLE_ENTITY,
                ErrorCode::FAILED_TO_DELETE_LOCATION
            );
        }
    }
}
