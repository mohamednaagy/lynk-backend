<?php

namespace App\Http\Controllers\Api\V1\Lender\Enquiries;

use App\Actions\Contracts\Enquiries\CreateEnquiry;
use App\Actions\Contracts\Enquiries\ListUserEnquiries;
use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Lender\Enquiries\StoreEnquiryRequest;
use App\Models\Enquiry;
use App\Transformers\EnquiryTransformer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EnquiryController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param  Request  $request
     * @param  ListUserEnquiries  $listUserEnquiries
     * @return JsonResponse
     */
    public function index(
        Request $request,
        ListUserEnquiries $listUserEnquiries
    ): JsonResponse {
        $enquiries = $listUserEnquiries->handle($request->user()->id);

        return fractal($enquiries, new EnquiryTransformer())->respond();
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  StoreEnquiryRequest  $storeEnquiryRequest
     * @param  CreateEnquiry  $createEnquiry
     * @return JsonResponse
     */
    public function store(StoreEnquiryRequest $storeEnquiryRequest, CreateEnquiry $createEnquiry): JsonResponse
    {
        $data = $storeEnquiryRequest->validated();
        $data['user_id'] = ($user = $storeEnquiryRequest->user())->id;
        $data['role_id'] = $user->roles()
            ->whereIn('name', [
                Role::LenderAdmin,
                Role::LenderSupervisor,
                Role::LenderBilling,
                Role::LenderOrderCreator,
            ])
            ->firstOrFail()
            ->id;

        $enquiry = $createEnquiry->handle($data);

        return fractal($enquiry, new EnquiryTransformer())->respond();
    }

    /**
     * Display the specified resource.
     *
     * @param  Enquiry  $enquiry
     * @return JsonResponse
     */
    public function show(Enquiry $enquiry): JsonResponse
    {
        return fractal($enquiry, new EnquiryTransformer())
            ->parseIncludes(['creator', 'body'])
            ->respond();
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
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
