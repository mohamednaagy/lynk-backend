<?php

namespace App\Http\Controllers\Api\V1\Visitor\Enquiries;

use App\Actions\Contracts\Enquiries\CreateEnquiry;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Enquiries\StoreEnquiryRequest;
use Illuminate\Http\JsonResponse;

class CreateVisitorEnquiry extends Controller
{
    public function __invoke(StoreEnquiryRequest $storeEnquiryRequest, CreateEnquiry $createEnquiry): JsonResponse
    {
//        dd($storeEnquiryRequest->validated());
        $enquiry = $createEnquiry->handle($storeEnquiryRequest->validated());

        return $this->successResponse([$enquiry]);
    }
}
