<?php

namespace App\Http\Controllers\Api\V1\Admin\Enquiries;

use App\Actions\Contracts\Enquiries\ReplyToEnquiry as ReplyToEnquiryInterface;
use App\Enums\Area;
use App\Enums\EnquiryStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Admin\Enquiries\ReplyToEnquiryRequest;
use App\Mail\ReplyToVisitorEnquiry;
use App\Models\Enquiry;
use App\Transformers\EnquiryReplyTransformer;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

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
        $enquiryReplies = $enquiry->replies()->latest()->get();

        return fractal($enquiryReplies, new EnquiryReplyTransformer())
            ->parseIncludes(['creator'])
            ->respond();
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  ReplyToEnquiryRequest  $replyToEnquiryRequest
     * @param  ReplyToEnquiryInterface  $replyToEnquiry
     * @param  Enquiry  $enquiry
     * @return JsonResponse
     */
    public function store(
        ReplyToEnquiryRequest $replyToEnquiryRequest,
        ReplyToEnquiryInterface $replyToEnquiry,
        Enquiry $enquiry
    ): JsonResponse {
        return DB::transaction(function () use ($replyToEnquiryRequest, $replyToEnquiry, $enquiry) {
            // check if the enquiry is closed already
            if ($enquiry->status->is(EnquiryStatus::Closed)) {
                return $this->errorResponse(
                    __('error.enquiry_closed_already')
                );
            }

            // create the enquiry reply
            $data = $replyToEnquiryRequest->validated();
            $data['enquiry_id'] = $enquiry->id;
            $data['user_id'] = ($user = $replyToEnquiryRequest->user())->id;
            $data['role_id'] = $user->roles()
                ->whereIn('name', Area::getRolesPerAreaMap()[Area::SuperAdmin])
                ->firstOrFail()
                ->id;
            $enquiryReply = $replyToEnquiry->handle($data);

            // change the enquiry status to be resolved
            if ($enquiry->status->is(EnquiryStatus::UnderReview)) {
                $enquiry->update([
                    'status' => ! empty($data['status']) ? $data['status'] : EnquiryStatus::Resolved,
                ]);
            }

            // send email to notify the visitor with the reply
            if ($enquiry->email) {
                $invitationUrl = $replyToEnquiryRequest->validated('redirect_url');
                Mail::to($enquiry->email)->send(new ReplyToVisitorEnquiry($enquiry, $invitationUrl));
            }

            return fractal($enquiryReply, new EnquiryReplyTransformer())
                ->parseIncludes(['creator'])
                ->respond();
        });
    }
}
