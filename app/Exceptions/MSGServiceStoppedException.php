<?php

namespace App\Exceptions;

use Exception;
use Throwable;

class MSGServiceStoppedException extends Exception
{
    public function __construct(
        string $message = '',
        protected array $context = [],
        ?Throwable $previous = null
    ) {
        $message = $this->formatMessage($message, $context);

        parent::__construct($message, 0, $previous);
    }

    public function formatMessage($message, $context): string
    {
        $messageParts = array_filter([
            'MSEGAT_SERVICE_ERROR',
            $context['body'] ?? null,
            $context['response'] ?? null,
            $message,
        ]);

        return implode(' | ', $messageParts);
    }
}
