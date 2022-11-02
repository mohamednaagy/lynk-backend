<?php

namespace App\Http\Controllers\Api\V1\Lender\Enquiries\Replies;

use App\Actions\Contracts\Enquiries\ReplyToEnquiry as ReplyToEnquiryInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Lender\Enquiries\Replies\StoreReplyToEnquiryRequest;
use App\Models\Enquiry;
use App\Transformers\EnquiryReplyTransformer;
use Illuminate\Http\JsonResponse;

class ReplyToEnquiry extends Controller
{
    /**
     * Store a newly created resource in storage.
     *
     * @param StoreReplyToEnquiryRequest $storeReplyToEnquiryRequest
     * @param ReplyToEnquiryInterface $replyToEnquiry
     * @return JsonResponse
     */
    public function __invoke(
        Enquiry $enquiry,
        StoreReplyToEnquiryRequest $storeReplyToEnquiryRequest,
        ReplyToEnquiryInterface $replyToEnquiry
    ): JsonResponse {
        $data = $storeReplyToEnquiryRequest->validated();
        $data['user_id'] = $storeReplyToEnquiryRequest->user()->id;
        $data['enquiry_id'] = $enquiry->id;

        $enquiryReply = $replyToEnquiry->handle($data);

        return fractal($enquiryReply, new EnquiryReplyTransformer())->respond();
    }

}
