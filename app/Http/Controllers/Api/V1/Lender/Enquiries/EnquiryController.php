<?php

namespace App\Http\Controllers\Api\V1\Lender\Enquiries;

use App\Actions\Contracts\Enquiries\CreateEnquiry;
use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Enquiries\StoreEnquiryRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EnquiryController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  StoreEnquiryRequest  $storeEnquiryRequest
     * @return JsonResponse
     */
    public function store(StoreEnquiryRequest $storeEnquiryRequest, CreateEnquiry $createEnquiry)
    {
        $data = $storeEnquiryRequest->validated();
        $data['user_id'] = $storeEnquiryRequest->user()->id;
        $data['role'] = Role::LenderAdmin;

        $enquiry = $createEnquiry->handle($data);

        return $this->successResponse([$enquiry]);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
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
