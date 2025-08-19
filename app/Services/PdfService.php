<?php

namespace App\Services;

use App\Contracts\Services\PdfServiceInterface;
use App\Enums\DocumentType;
use App\Support\DocumentEngine\PdfFactory;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class PdfService implements PdfServiceInterface
{
    /**
     * Generate or retrieve PDF document
     */
    public function generateOrRetrievePdf(DocumentType $documentType, array $context): array
    {
        try {
            // Validate context based on document type
            $this->validateContext($documentType, $context);

            // Check if PDF already exists
            if ($this->pdfExists($documentType, $context)) {
                $path = $this->getPdfPath($documentType, $context);

                return [
                    'success' => true,
                    'message' => 'PDF retrieved successfully',
                    'data' => [
                        'path' => $path,
                        'is_newly_generated' => false,
                    ],
                ];
            }

            // Generate new PDF
            $pdf = PdfFactory::make($documentType->value);
            $pdf->setContext($context);
            $pdf->generate();

            $path = $this->getPdfPath($documentType, $context);

            return [
                'success' => true,
                'message' => 'PDF generated successfully',
                'data' => [
                    'path' => $path,
                    'is_newly_generated' => true,
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
     * Check if PDF already exists
     */
    public function pdfExists(DocumentType $documentType, array $context): bool
    {
        try {
            $pdf = PdfFactory::make($documentType->value);
            $pdf->setContext($context);

            return $pdf->checkIfGeneratedBefore();
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get PDF file path if exists
     */
    public function getPdfPath(DocumentType $documentType, array $context): ?string
    {
        try {
            $pdf = PdfFactory::make($documentType->value);
            $pdf->setContext($context);

            if ($pdf->checkIfGeneratedBefore()) {
                return $pdf->getExistingPdfPath();
            }

            return null;
        } catch (\Exception $e) {
            return null;
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
