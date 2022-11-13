<?php

namespace App\Http\Controllers\Api\V1\Visitor\Enquiries;

use App\Http\Controllers\Controller;
use App\Models\Enquiry;
use App\Transformers\EnquiryTransformer;
use Illuminate\Http\JsonResponse;

class GetVisitorEnquiry extends Controller
{
    public function __invoke(Enquiry $enquiry): JsonResponse
    {
        return fractal($enquiry, new EnquiryTransformer())
            ->parseIncludes(['body'])
            ->respond();
    }
}
