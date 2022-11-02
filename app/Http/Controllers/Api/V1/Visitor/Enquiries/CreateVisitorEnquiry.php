<?php

namespace App\Http\Controllers\Api\V1\Visitor\Enquiries;

use App\Actions\Contracts\Enquiries\CreateEnquiry;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Visitor\Enquiries\StoreVisitorEnquiryRequest;
use App\Transformers\EnquiryTransformer;
use Illuminate\Http\JsonResponse;

class CreateVisitorEnquiry extends Controller
{
    public function __invoke(StoreVisitorEnquiryRequest $storeEnquiryRequest, CreateEnquiry $createEnquiry): JsonResponse
    {
        $enquiry = $createEnquiry->handle($storeEnquiryRequest->validated());

        return fractal($enquiry, new EnquiryTransformer())->respond();
    }
}
