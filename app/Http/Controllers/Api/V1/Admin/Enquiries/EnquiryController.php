<?php

namespace App\Http\Controllers\Api\V1\Admin\Enquiries;

use App\Actions\Contracts\Enquiries\GetPaginatedEnquiries;
use App\Http\Controllers\Controller;
use App\Models\Enquiry;
use App\Transformers\EnquiryTransformer;
use Illuminate\Http\JsonResponse;

class EnquiryController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param  GetPaginatedEnquiries  $getPaginatedEnquiries
     * @return JsonResponse
     */
    public function index(GetPaginatedEnquiries $getPaginatedEnquiries): JsonResponse
    {
        return fractal($getPaginatedEnquiries->handle(), new EnquiryTransformer())
            ->parseIncludes([
                'id',
                'subject',
                'status',
                'creation_date',
                'creator',
            ])
            ->respond();
    }

    /**
     * Display the specified resource.
     *
     * @param  Enquiry  $enquiry
     * @return JsonResponse
     */
    public function show(Enquiry $enquiry): JsonResponse
    {
        return fractal($enquiry, new EnquiryTransformer())
            ->parseIncludes([
                'id',
                'subject',
                'status',
                'creation_date',
                'creator',
                'body',
            ])
            ->respond();
    }
}
