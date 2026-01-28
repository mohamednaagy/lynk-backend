<?php

namespace App\Actions\Contracts;

use App\Enums\DocumentType;

interface PdfService
{
    /**
     * Generate or retrieve PDF document
     */
    public function generateOrRetrievePdf(DocumentType $documentType, array $context): array;
}
