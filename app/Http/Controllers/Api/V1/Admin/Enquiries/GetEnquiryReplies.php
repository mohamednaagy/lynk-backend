<?php

namespace App\Http\Controllers\Api\V1\Admin\Enquiries;

use App\Http\Controllers\Controller;
use App\Models\Enquiry;
use App\Transformers\EnquiryReplyTransformer;
use Illuminate\Http\JsonResponse;

class GetEnquiryReplies extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  Enquiry  $enquiry
     * @return JsonResponse
     */
    public function __invoke(Enquiry $enquiry): JsonResponse
    {
        $enquiryReplies = $enquiry->load('replies')->replies()->latest()->get();

        return fractal($enquiryReplies, new EnquiryReplyTransformer())
            ->parseIncludes(['creator'])
            ->respond();
    }
}
