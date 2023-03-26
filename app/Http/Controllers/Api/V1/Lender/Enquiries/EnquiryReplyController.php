<?php

namespace App\Http\Controllers\Api\V1\Lender\Enquiries;

use App\Actions\Contracts\Enquiries\ReplyToEnquiry as ReplyToEnquiryInterface;
use App\Enums\EnquiryStatus;
use App\Enums\ErrorCode;
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
        $this->authorize('view', $enquiry);

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

    /**
     * Store a newly created resource in storage.
     *
     * @param  Enquiry  $enquiry
     * @param  StoreReplyToEnquiryRequest  $request
     * @param  ReplyToEnquiryInterface  $replyToEnquiry
     * @return JsonResponse
     */
    public function store(
        StoreReplyToEnquiryRequest $request,
        ReplyToEnquiryInterface $replyToEnquiry,
        Enquiry $enquiry,
    ): JsonResponse {
        return DB::transaction(function () use ($request, $replyToEnquiry, $enquiry) {
            $this->authorize('view', $enquiry);

            // check if the enquiry is closed already
            if ($enquiry->status->is(EnquiryStatus::Closed)) {
                return $this->errorResponse(
                    __('error.enquiry_closed_already'),
                    code: ErrorCode::ENQUIRY_CLOSED_ALREADY
                );
            }

            $data = $request->validated();
            $user = $request->user();

            $data['user_id'] = $user->id;
            $data['enquiry_id'] = $enquiry->id;
            $data['role_id'] = $user->roles->first()->id;

            $enquiryReply = $replyToEnquiry->handle($data);

            // change the enquiry status to be under review
            if ($enquiry->status->is(EnquiryStatus::Resolved)) {
                $enquiry->update([
                    'status' => EnquiryStatus::UnderReview,
                ]);
            }

            return fractal($enquiryReply, new EnquiryReplyTransformer())
                ->parseIncludes([
                    'id',
                    'body',
                    'creation_date',
                    'creator',
                ])
                ->respond();
        });
    }
}
