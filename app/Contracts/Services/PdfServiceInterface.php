<?php

namespace App\Contracts\Services;

use App\Enums\DocumentType;

interface PdfServiceInterface
{
    /**
     * Generate or retrieve PDF document
     */
    public function generateOrRetrievePdf(DocumentType $documentType, array $context): array;
}
