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
use Illuminate\Support\Facades\Storage;

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
                return Storage::disk($result['data']['disk'])->download($result['data']['path']);
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
