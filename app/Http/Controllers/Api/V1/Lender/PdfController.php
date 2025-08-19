<?php

namespace App\Http\Controllers\Api\V1\Lender;

use App\Http\Controllers\Api\V1\PdfController as BasePdfController;
use App\Http\Requests\GeneratePdfRequest;
use Illuminate\Http\JsonResponse;

class PdfController extends BasePdfController
{
    /**
     * Generate or retrieve PDF document for lender users
     */
    public function generate(GeneratePdfRequest $request): JsonResponse
    {
        // Add lender-specific logging or validation if needed
        return parent::generate($request);
    }

    /**
     * Get available document types for lender users
     */
    public function getDocumentTypes(): JsonResponse
    {
        // Lender users can see all document types
        return parent::getDocumentTypes();
    }

    /**
     * Check if PDF exists for given document type and context
     */
    public function checkPdfExists(\Illuminate\Http\Request $request): JsonResponse
    {
        return parent::checkPdfExists($request);
    }
}
