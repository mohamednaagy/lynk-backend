<?php

namespace App\Http\Controllers\Api\V1\Admin\Companies\LenderClients;

use App\Http\Controllers\Controller;
use App\Models\ClientAutoSellPeriod;
use App\Models\CompanyLenderClient;
use App\Models\Lender;
use App\Models\Media;
use Illuminate\Http\JsonResponse;

class DeleteAutoSellPeriodSupportingDocument extends Controller
{
    /**
     * Delete a supporting document for the auto sell period.
     */
    public function __invoke(
        Lender $lender,
        CompanyLenderClient $client,
        ClientAutoSellPeriod $client_auto_sell_period,
        Media $media
    ): JsonResponse {
        // Delete the media
        if ($media->delete()) {
            return $this->successResponse([
                'message' => __('common.supporting_document_deleted_successfully'),
            ]);
        }

        return $this->errorResponse(__('error.supporting_document_not_found'));
    }
}
