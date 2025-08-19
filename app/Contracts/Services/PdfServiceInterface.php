<?php

namespace App\Contracts\Services;

use App\Enums\DocumentType;

interface PdfServiceInterface
{
    /**
     * Generate or retrieve PDF document
     */
    public function generateOrRetrievePdf(DocumentType $documentType, array $context): array;

    /**
     * Check if PDF already exists
     */
    public function pdfExists(DocumentType $documentType, array $context): bool;

    /**
     * Get PDF file path if exists
     */
    public function getPdfPath(DocumentType $documentType, array $context): ?string;
}
