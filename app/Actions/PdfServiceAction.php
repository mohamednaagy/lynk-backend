<?php

namespace App\Actions;

use App\Actions\Contracts\PdfService;
use App\Enums\DocumentType;
use App\Support\DocumentEngine\PdfFactory;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class PdfServiceAction implements PdfService
{
    /**
     * Generate or retrieve PDF document
     */
    public function generateOrRetrievePdf(DocumentType $documentType, array $context): array
    {
        try {
            $this->validateContext($documentType, $context);

            $pdf = PdfFactory::make($documentType->value);
            $pdf->setContext($context);
            $pdf->generate();

            return [
                'success' => true,
                'message' => 'PDF generated successfully',
                'data' => [
                    'path' => $pdf->getExistingPdfPath(),
                    'disk' => $pdf->getDisk(),
                ],
            ];
        } catch (InvalidArgumentException $e) {
            Log::error('Invalid document type requested', [
                'document_type' => $documentType->value,
                'context' => $context,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Invalid document type',
                'error' => $e->getMessage(),
            ];
        } catch (\Exception $e) {
            Log::error('Error generating PDF', [
                'document_type' => $documentType->value,
                'context' => $context,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'message' => 'Error generating PDF',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Validate context based on document type requirements
     *
     * @throws InvalidArgumentException
     */
    private function validateContext(DocumentType $documentType, array $context): void
    {
        if ($documentType->requiresTraderOrder() && ! isset($context['traderOrder'])) {
            throw new InvalidArgumentException("Document type '{$documentType->value}' requires trader order context");
        }

        if ($documentType->requiresTransaction() && ! isset($context['transaction'])) {
            throw new InvalidArgumentException("Document type '{$documentType->value}' requires transaction context");
        }
    }
}
