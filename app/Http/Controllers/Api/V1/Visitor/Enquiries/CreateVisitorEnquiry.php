<?php

namespace App\Http\Controllers\Api\V1\Visitor\Enquiries;

use App\Actions\Contracts\Enquiries\CreateEnquiry;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Visitor\Enquiries\StoreVisitorEnquiryRequest;
use App\Mail\AccessVisitorEnquiry;
use App\Transformers\EnquiryTransformer;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class CreateVisitorEnquiry extends Controller
{
    public function __invoke(StoreVisitorEnquiryRequest $request, CreateEnquiry $createEnquiry): JsonResponse
    {
        return DB::transaction(function () use ($request, $createEnquiry) {
            $enquiry = $createEnquiry->handle($request->validated());

            $invitationUrl = $request->validated('redirect_url');
            Mail::to($enquiry->email)->send(new AccessVisitorEnquiry($enquiry, $invitationUrl));

            return fractal($enquiry, new EnquiryTransformer())
                ->parseIncludes([
                    'id',
                    'subject',
                    'status',
                    'creation_date',
                ])
                ->respond();
        });
    }
}
