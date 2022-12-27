<?php

namespace App\Http\Controllers\Api\V1\Visitor\Enquiries;

use App\Http\Controllers\Controller;
use App\Models\Enquiry;
use App\Transformers\EnquiryTransformer;
use Illuminate\Http\JsonResponse;

class GetVisitorEnquiry extends Controller
{
    public function __construct()
    {
        $this->middleware(['signed', 'throttle:6,1']);
    }

    public function __invoke(Enquiry $enquiry): JsonResponse
    {
        $enquiry->load(['replies' => function ($query) {
            $query->latest();
        }]);

        return fractal($enquiry, new EnquiryTransformer())
            ->parseIncludes([
                'id',
                'subject',
                'status',
                'creation_date',
                'body',
                'replies.id',
                'replies.body',
                'replies.creation_date',
                'replies.creator',
                'replySignature',
            ])
            ->respond();
    }
}
