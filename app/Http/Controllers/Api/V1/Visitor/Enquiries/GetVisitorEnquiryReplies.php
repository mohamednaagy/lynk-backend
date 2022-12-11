<?php

namespace App\Http\Controllers\Api\V1\Visitor\Enquiries;

use App\Http\Controllers\Controller;
use App\Models\Enquiry;
use App\Transformers\EnquiryReplyTransformer;
use Illuminate\Http\JsonResponse;

class GetVisitorEnquiryReplies extends Controller
{
    public function __invoke(Enquiry $enquiry): JsonResponse
    {
        $enquiry->load(['replies' => function ($query) {
            $query->latest();
        }]);

        return fractal($enquiry->replies, new EnquiryReplyTransformer())
            ->parseIncludes([
                'id',
                'body',
                'creation_date',
                'creator',
            ])
            ->respond();
    }
}
