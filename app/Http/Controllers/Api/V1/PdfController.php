<?php

namespace App\Http\Controllers\Api\V1;

use App\Contracts\Services\PdfServiceInterface;
use App\Enums\DocumentType;
use App\Http\Controllers\Controller;
use App\Http\Requests\GeneratePdfRequest;
use App\Models\TraderOrder;
use App\Models\Transaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PdfController extends Controller
{
    public function __construct(
        private PdfServiceInterface $pdfService
    ) {}

    /**
     * Generate or retrieve PDF document
     */
    public function generate(GeneratePdfRequest $request): JsonResponse
    {
        try {
            $documentType = DocumentType::fromValue($request->document_type);
            $context = $this->buildContext($request);

            $result = $this->pdfService->generateOrRetrievePdf($documentType, $context);

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'message' => $result['message'],
                    'data' => $result['data'],
                ], 200);
            }

            return response()->json([
                'success' => false,
                'message' => $result['message'],
                'error' => $result['error'] ?? null,
            ], 400);

        } catch (\Exception $e) {
            Log::error('PDF generation failed', [
                'request' => $request->all(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Internal server error',
                'error' => config('app.debug') ? $e->getMessage() : 'Something went wrong',
            ], 500);
        }
    }

    /**
     * Get available document types
     */
    public function getDocumentTypes(): JsonResponse
    {
        $documentTypes = collect(DocumentType::getValues())->map(function ($type) {
            $enum = DocumentType::fromValue($type);

            return [
                'value' => $type,
                'label' => __('enums.document_type.'.$type),
                'requires_trader_order' => $enum->requiresTraderOrder(),
                'requires_transaction' => $enum->requiresTransaction(),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $documentTypes,
        ]);
    }

    /**
     * Check if PDF exists for given document type and context
     */
    public function checkPdfExists(Request $request): JsonResponse
    {
        $request->validate([
            'document_type' => 'required|string|in:'.implode(',', DocumentType::getValues()),
            'context' => 'required|array',
            'context.trader_order_id' => 'nullable|integer|exists:trader_orders,id',
            'context.transaction_id' => 'nullable|integer|exists:transactions,id',
        ]);

        try {
            $documentType = DocumentType::fromValue($request->document_type);
            $context = $this->buildContext($request);

            $exists = $this->pdfService->pdfExists($documentType, $context);
            $path = $exists ? $this->pdfService->getPdfPath($documentType, $context) : null;

            return response()->json([
                'success' => true,
                'data' => [
                    'exists' => $exists,
                    'path' => $path,
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error checking PDF existence',
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Build context array for PDF generation
     */
    private function buildContext(Request $request): array
    {
        $context = [];

        if ($request->input('context.trader_order_id')) {
            $traderOrder = TraderOrder::findOrFail($request->input('context.trader_order_id'));
            $context['traderOrder'] = $traderOrder;
        }

        if ($request->input('context.transaction_id')) {
            $transaction = Transaction::findOrFail($request->input('context.transaction_id'));
            $context['transaction'] = $transaction;
        }

        return $context;
    }
}
