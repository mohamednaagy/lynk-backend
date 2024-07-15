<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
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
        $providerResponse = isset($context['provider_response_body']) ? 'Response Body : '.json_encode($context['provider_response_body']) : null;
        $failure_reason = isset($context['failure_reason']) ? 'Failure Reason : '.json_encode($context['failure_reason']) : null;

        $messageParts = array_filter([
            'TRADER_ERROR',
            $context['provider'] ?? null,
            'Trader Order ID '.Arr::get($context, 'trader_order_id', '---'),
            $context['version'] ?? null,
            $providerResponse,
            $failure_reason,
            $message,
        ]);

        Log::channel('bursam')->error('Trader Request Issue : ...', [
            'TRADER_ERROR',
            $context['provider'] ?? null,
            'Trader Order ID '.Arr::get($context, 'trader_order_id', '---'),
            $context['version'] ?? null,
            $providerResponse,
            $failure_reason,
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
