<?php

namespace App\Http\Controllers\Api\V1\Supplier\Location;

use App\Actions\Commodities\CommoditySupplier\UpdateSupplierLocationAction;
use App\Actions\Contracts\Commodities\CommodityLocation\CreateSupplierLocation;
use App\Actions\Contracts\Commodities\CommodityLocation\GetPaginatedSupplierLocations;
use App\Enums\Action;
use App\Enums\Area;
use App\Enums\Subject;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Supplier\Locations\StoreLocationRequest;
use App\Http\Requests\V1\Supplier\Locations\UpdateSupplierLocationRequest;
use App\Models\SupplierLocation as ModelsSupplierLocation;
use App\Transformers\SupplierLocationsTransformer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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

        return fractal($enquiries, new SupplierLocationsTransformer())
            ->parseIncludes([
                'id',
                'name',
                'unique_identifier',
                'description',
                'created_at',
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

        return fractal($location, new SupplierLocationsTransformer())
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
    public function update(UpdateSupplierLocationRequest $updateSupplierLocationRequest, ModelsSupplierLocation $location, UpdateSupplierLocationAction $updateSupplierLocation): JsonResponse
    {
        $location = $updateSupplierLocation->handle($location, $updateSupplierLocationRequest->validated());

        return fractal($location, new SupplierLocationsTransformer())
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
        return fractal($location, new SupplierLocationsTransformer())
            ->parseIncludes([
                'id',
                'unique_identifier',
                'name',
                'description',
                'created_at',
            ])
            ->respond();
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
