<?php

namespace App\Http\Controllers\Api\V1\Admin\Companies\LenderClients;

use App\Enums\Action;
use App\Enums\Area;
use App\Enums\MediaCollections\ClientAutoSellPeriodMediaCollection;
use App\Enums\Subject;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Admin\Companies\LenderClients\UploadAutoSellPeriodSupportingDocumentRequest;
use App\Models\ClientAutoSellPeriod;
use App\Models\CompanyLenderClient;
use App\Models\Lender;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class UploadAutoSellPeriodSupportingDocument extends Controller
{
    public function __construct()
    {
        $this->middleware(
            'permission:'.
            perm(Area::SuperAdmin, [Subject::LenderClientAutoSellPeriodDocuments, Action::Create, Action::Manage])
        );

    }

    /**
     * Upload a supporting document for the auto sell period.
     */
    public function __invoke(
        UploadAutoSellPeriodSupportingDocumentRequest $request,
        Lender $lender,
        CompanyLenderClient $client,
        ClientAutoSellPeriod $client_auto_sell_period
    ): JsonResponse {
        $media = $client_auto_sell_period
            ->addMediaFromRequest('media')
            ->toMediaCollection(ClientAutoSellPeriodMediaCollection::SupportingDocument);

        return response()->json([
            'message' => __('common.supporting_document_uploaded_successfully'),
            'data' => [
                'id' => $media->id,
                'name' => $media->name,
                'collection' => $media->collection_name,
                'url' => $media->getUrl(),
            ],
        ], Response::HTTP_CREATED);
    }
}
