<?php

namespace App\Http\Controllers\Api\V1\Lender\Enquiries\Replies;

use App\Models\Enquiry;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Transformers\EnquiryReplyTransformer;

class ListEnquiryReplies extends Controller
{
    /**
     * Store a newly created resource in storage.
     *
     * @param Enquiry $enquiry
     * @return JsonResponse
     */
    public function __invoke(
        Enquiry $enquiry
    ): JsonResponse {

        $replies = $enquiry->load('replies')->replies()->latest()->get();

        return fractal($replies, new EnquiryReplyTransformer())
            ->respond();
    }

}
