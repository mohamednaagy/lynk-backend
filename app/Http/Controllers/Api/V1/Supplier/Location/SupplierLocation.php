<?php

namespace App\Http\Controllers\Api\V1\Supplier\Location;

use App\Actions\Contracts\Commodities\CommodityLocation\CreateSupplierLocation;
use App\Actions\Contracts\Commodities\CommodityLocation\DeleteSupplierLocation;
use App\Actions\Contracts\Commodities\CommodityLocation\GetPaginatedSupplierLocations;
use App\Actions\Contracts\Commodities\CommodityLocation\UpdateSupplierLocation;
use App\Enums\Action;
use App\Enums\Area;
use App\Enums\ErrorCode;
use App\Enums\Subject;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Supplier\Locations\StoreLocationRequest;
use App\Http\Requests\V1\Supplier\Locations\UpdateSupplierLocationRequest;
use App\Models\SupplierLocation as ModelsSupplierLocation;
use App\Transformers\SupplierLocationsTransformer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class SupplierLocation extends Controller
{
    public function __construct()
    {
        $this->middleware(
            'permission:'.
                perm(Area::CommoditySupplier, [Subject::CommoditySupplierLocations, Action::Manage, Action::Index])
        )
            ->only('index');

        $this->middleware(
            'permission:'.
                perm(Area::CommoditySupplier, [Subject::CommoditySupplierLocations, Action::Manage, Action::Create])
        )
            ->only('store');

        $this->middleware(
            'permission:'.
                perm(Area::CommoditySupplier, [Subject::CommoditySupplierLocations, Action::Manage, Action::Edit])
        )->only('update');

        $this->middleware(
            'permission:'.
                perm(Area::CommoditySupplier, [Subject::CommoditySupplierLocations, Action::Manage, Action::Show])
        )->only('show');

        $this->middleware(
            'permission:'.
                perm(Area::CommoditySupplier, [Subject::CommoditySupplierLocations, Action::Manage, Action::Delete])
        )->only('destroy');
    }

    /**
     * Display a listing of the resource.
     */
    public function index(
        Request $request,
        GetPaginatedSupplierLocations $getPaginatedSupplierLocations
    ): JsonResponse {
        $supplier = tenant()->supplier;
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
    public function store(StoreLocationRequest $storeLocationRequest, CreateSupplierLocation $createSupplierLocation): JsonResponse
    {
        $data = $storeLocationRequest->validated();
        $data['company_id'] = tenant()->id;
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
     * Update the specified resource in storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateSupplierLocationRequest $updateSupplierLocationRequest, ModelsSupplierLocation $location, UpdateSupplierLocation $updateSupplierLocation): JsonResponse
    {
        $location = $updateSupplierLocation->handle($location, $updateSupplierLocationRequest->validated());

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
     * Display the specified resource.
     */
    public function show(ModelsSupplierLocation $location): JsonResponse
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
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(ModelsSupplierLocation $location, DeleteSupplierLocation $deleteSupplierLocation)
    {
        //check if the commodity item is deleteable
        if (! $location->is_deletable) {
            return $this->errorResponse(
                __('error.location_cannot_be_deleted'),
                Response::HTTP_BAD_REQUEST,
                ErrorCode::LOCATION_NOT_DELETABLE
            );
        }

        try {
            $deleteSupplierLocation->handle($location);

            return $this->successResponse();
        } catch (\Exception $e) {
            Log::error("Failed to delete commodity item ID: {$location->id}. Error: {$e->getMessage()}");

            return $this->errorResponse(
                __('error.failed_to_delete_location'),
                Response::HTTP_UNPROCESSABLE_ENTITY,
                ErrorCode::FAILED_TO_DELETE_LOCATION
            );
        }
    }
}
