<?php

namespace App\Http\Controllers\Api\V1\Admin\Companies\LenderClients;

use App\Enums\MediaCollections\ClientAutoSellPeriodMediaCollection;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Admin\Companies\LenderClients\UploadAutoSellPeriodSupportingDocumentRequest;
use App\Models\ClientAutoSellPeriod;
use App\Models\CompanyLenderClient;
use App\Models\Lender;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class UploadAutoSellPeriodSupportingDocument extends Controller
{
    /**
     * Upload a supporting document for the auto sell period.
     */
    public function __invoke(
        UploadAutoSellPeriodSupportingDocumentRequest $request,
        Lender $lender,
        CompanyLenderClient $client,
        ClientAutoSellPeriod $client_auto_sell_period
    ): JsonResponse {
        // Validate relationships to ensure security
        $this->validateRelationships($lender, $client, $client_auto_sell_period);

        $media = $client_auto_sell_period
            ->addMediaFromRequest('media')
            ->toMediaCollection(ClientAutoSellPeriodMediaCollection::SupportingDocument);

        return response()->json([
            'message' => __('common.supporting_document_uploaded_successfully'),
            'data' => [
                'id' => $media->id,
                'name' => $media->name,
                'url' => $media->getUrl(),
            ],
        ], Response::HTTP_CREATED);
    }

    /**
     * Validate that the resources are properly related to prevent unauthorized access.
     */
    private function validateRelationships(
        Lender $lender,
        CompanyLenderClient $client,
        ClientAutoSellPeriod $client_auto_sell_period
    ): void {
        // Check if client belongs to the lender
        if ($client->company_id !== $lender->id) {
            throw new NotFoundHttpException(__('error.client_does_not_belong_to_lender'));
        }

        // Check if auto sell period belongs to the client
        if ($client_auto_sell_period->company_lender_client_id !== $client->id) {
            throw new NotFoundHttpException(__('error.auto_sell_period_does_not_belong_to_client'));
        }
    }
}
