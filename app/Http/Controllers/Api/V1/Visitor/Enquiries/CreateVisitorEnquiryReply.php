<?php

namespace App\Http\Controllers\Api\V1\Visitor\Enquiries;

use App\Actions\Contracts\Enquiries\ReplyToEnquiry;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Visitor\Enquiries\StoreVisitorEnquiryReply;
use App\Models\Enquiry;
use App\Transformers\EnquiryReplyTransformer;

class CreateVisitorEnquiryReply extends Controller
{
    public function __construct()
    {
        $this->middleware(['signed', 'throttle:6,1']);
    }

    /**
     * Summary of __invoke
     *
     * @param  StoreVisitorEnquiryReply  $request
     * @param  ReplyToEnquiry  $replyToEnquiry
     * @return \Illuminate\Http\JsonResponse
     */
    public function __invoke(StoreVisitorEnquiryReply $request, ReplyToEnquiry $replyToEnquiry, Enquiry $enquiry)
    {
        $data = array_merge(
            $request->validated(),
            [
                'enquiry_id' => $enquiry->id,
            ]
        );

        return fractal($replyToEnquiry->handle($data), new EnquiryReplyTransformer())->respond();
    }
}
