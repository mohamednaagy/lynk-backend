<?php

namespace App\Http\Controllers\Api\V1\Lender\Enquiries;

use App\Actions\Contracts\Enquiries\ReplyToEnquiry as ReplyToEnquiryInterface;
use App\Enums\EnquiryStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Lender\Enquiries\Replies\StoreReplyToEnquiryRequest;
use App\Models\Enquiry;
use App\Transformers\EnquiryReplyTransformer;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class EnquiryReplyController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param  Enquiry  $enquiry
     * @return JsonResponse
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
     * @param  Enquiry  $enquiry
     * @param  StoreReplyToEnquiryRequest  $storeReplyToEnquiryRequest
     * @param  ReplyToEnquiryInterface  $replyToEnquiry
     * @return JsonResponse
     */
    public function store(
        Enquiry $enquiry,
        StoreReplyToEnquiryRequest $storeReplyToEnquiryRequest,
        ReplyToEnquiryInterface $replyToEnquiry
    ): JsonResponse {
        return DB::transaction(function () use ($storeReplyToEnquiryRequest, $replyToEnquiry, $enquiry) {
            // check if the enquiry is closed already
            if ($enquiry->status->is(EnquiryStatus::Closed)) {
                return $this->errorResponse(
                    __('error.enquiry_closed_already')
                );
            }

            $data = $storeReplyToEnquiryRequest->validated();
            $data['user_id'] = $storeReplyToEnquiryRequest->user()->id;
            $data['enquiry_id'] = $enquiry->id;

            $enquiryReply = $replyToEnquiry->handle($data);

            // change the enquiry status to be under review
            if ($enquiry->status->is(EnquiryStatus::Resolved)) {
                $enquiry->update([
                    'status' => EnquiryStatus::UnderReview,
                ]);
            }

            return fractal($enquiryReply, new EnquiryReplyTransformer())->respond();
        });
    }
}
