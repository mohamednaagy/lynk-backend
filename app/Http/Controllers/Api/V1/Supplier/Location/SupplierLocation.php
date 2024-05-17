<?php

namespace App\Http\Controllers\Api\V1\Supplier\Location;

use App\Actions\Contracts\Commodities\CommodityLocation\CreateSupplierLocation;
use App\Actions\Contracts\Commodities\CommodityLocation\GetPaginatedSupplierLocations;
use App\Enums\Action;
use App\Enums\Area;
use App\Enums\Role;
use App\Enums\Subject;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Supplier\Locations\StoreLocationRequest;
use App\Models\Company;
use App\Models\Enquiry;
use App\Transformers\EnquiryTransformer;
use App\Transformers\SupplierLocationsTransformer;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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
    }

    /**
     * Display a listing of the resource.
     */
    public function index(
        Request $request,
        GetPaginatedSupplierLocations $getPaginatedSupplierLocations
    ): JsonResponse {
        // TODO_LOCAL_MARKET we need to get company id from tenant not from auth
        $supplier = Auth::user()->supplier;

        $enquiries = $getPaginatedSupplierLocations->handle($supplier);

        return fractal($enquiries, new SupplierLocationsTransformer())
            ->parseIncludes([
                'id',
                'name',
                'unique_Identifier',
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
        // TODO_LOCAL_MARKET we need to get company id from tenant not from auth
        $data['company_id'] = Auth::user()->company_id;
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
     * Display the specified resource.
     *
     *
     * @throws AuthorizationException
     */
    public function show(Request $request, Enquiry $enquiry): JsonResponse
    {
        $authUser = $request->user();

        if (
            $this->doesEnquiryBelongToCurrentLender($authUser, $enquiry)
            || $this->doesEnquiryBelongToCurrentLender($authUser, $enquiry)
        ) {
            return fractal($enquiry, new EnquiryTransformer())
                ->parseIncludes([
                    'id',
                    'subject',
                    'status',
                    'creation_date',
                    'creator',
                    'body',
                ])
                ->respond();
        }

        throw new ModelNotFoundException();
    }

    protected function doesEnquiryBelongToCurrentLender($authUser, $enquiry)
    {
        return $authUser->company_id !== null
            && $authUser->hasRole(Role::LenderAdmin)
            && $enquiry->user?->company_id === $authUser->company_id;
    }

    protected function doesEnquiryBelongToCurrentUser($authUser, $enquiry)
    {
        return $authUser->id === $enquiry->user_id;
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
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
