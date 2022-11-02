<?php

namespace App\Http\Controllers\Api\V1\Admin\Enquiries;

use App\Actions\Contracts\Orders\ReplyToEnquiry as ReplyToEnquiryInterface;
use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Admin\Enquiries\ReplyToEnquiryRequest;
use App\Mail\ReplyToVisitorEnquiry;
use App\Models\Enquiry;
use App\Transformers\EnquiryReplyTransformer;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class ReplyToEnquiry extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  ReplyToEnquiryRequest  $replyToEnquiryRequest
     * @param  ReplyToEnquiryInterface  $replyToEnquiry
     * @param  Enquiry  $enquiry
     * @return JsonResponse
     */
    public function __invoke(ReplyToEnquiryRequest $replyToEnquiryRequest, ReplyToEnquiryInterface $replyToEnquiry, Enquiry $enquiry): JsonResponse
    {
        return DB::transaction(function () use ($replyToEnquiryRequest, $replyToEnquiry, $enquiry) {
            $data = $replyToEnquiryRequest->validated();
            $data['enquiry_id'] = $enquiry->id;
            $data['user_id'] = ($user = $replyToEnquiryRequest->user())->id;
            $data['role_id'] = $user->roles()
                ->whereIn('name', [
                    Role::Admin,
                ])
                ->firstOrFail()
                ->id;
            $enquiryReply = $replyToEnquiry->handle($data);

            if (! empty($data['redirect_url'])) {
                $invitationUrl = $replyToEnquiryRequest->safeInput('redirect_url');
                Mail::to($enquiry->email)->send(new ReplyToVisitorEnquiry($enquiry, $invitationUrl));
            }

            return fractal($enquiryReply, new EnquiryReplyTransformer())
                ->parseIncludes(['creator'])
                ->respond();
        });
    }
}
