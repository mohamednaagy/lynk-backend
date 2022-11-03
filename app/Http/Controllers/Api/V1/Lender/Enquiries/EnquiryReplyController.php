<?php

namespace App\Http\Controllers\Api\V1\Lender\Enquiries;

use App\Actions\Contracts\Enquiries\ReplyToEnquiry as ReplyToEnquiryInterface;
use App\Enums\EnquiryStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Lender\Enquiries\Replies\StoreReplyToEnquiryRequest;
use App\Models\Enquiry;
use App\Transformers\EnquiryReplyTransformer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EnquiryReplyController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Enquiry $enquiry): JsonResponse
    {
        $replies = $enquiry->replies()->latest()->get();

        return fractal($replies, new EnquiryReplyTransformer())
            ->respond();
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(
        Enquiry $enquiry,
        StoreReplyToEnquiryRequest $storeReplyToEnquiryRequest,
        ReplyToEnquiryInterface $replyToEnquiry
    ): JsonResponse {
        $data = $storeReplyToEnquiryRequest->validated();
        $data['user_id'] = $storeReplyToEnquiryRequest->user()->id;
        $data['enquiry_id'] = $enquiry->id;
        $enquiryReply =  DB::transaction(function () use ($data, $replyToEnquiry, $enquiry) {

            $enquiryReply = $replyToEnquiry->handle($data);
            $enquiry->update([
                'status'=> EnquiryStatus::UnderReview
            ]);

            return $enquiryReply;
        });


        return fractal($enquiryReply, new EnquiryReplyTransformer())->respond();
    }
}
