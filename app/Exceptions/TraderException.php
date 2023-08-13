<?php

namespace App\Exceptions;

use Exception;
use Throwable;

class TraderException extends Exception
{
    public function __construct(
        string $message = '',
        protected $context = [],
        ?Throwable $previous = null
    ) {
        $message = $this->formatMessage($message, $context);

        parent::__construct($message, 0, $previous);
    }

    public function formatMessage($message, $context)
    {
        $messageParts = array_filter([
            'TRADER_ERROR',
            ($context['provider'] ?? null),
            $context['version'] ?? null,
            $message,
        ]);

        return implode(' | ', $messageParts);
    }

    public function context()
    {
        return $this->context;
    }

    public function getContext($key = null)
    {
        if ($key) {
            return $this->context[$key];
        }

        return $this->context;
    }
}
