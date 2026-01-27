<?php

namespace App\Support\Webhooks\Jobs;

use Exception;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Spatie\WebhookServer\CallWebhookJob;

class LoggingWebhookJob extends CallWebhookJob
{
    public function handle(): void
    {

        Log::channel(LOG_CHANNEL_WEBHOOKS)->info('HTTP webhook request initiated', [
            'webhook_url' => $this->webhookUrl,
            'http_verb' => $this->httpVerb,
            'headers' => $this->headers,
            'payload_size' => strlen(json_encode($this->payload) ?: ''),
            'timeout' => $this->requestTimeout,
            'attempt' => $this->attempts(),
            'order_id' => $this->getOrderId(),
            'trader_order_id' => $this->getTraderOrderId(),
        ]);

        try {
            // Log the outgoing request details
            Log::channel(LOG_CHANNEL_WEBHOOKS)->debug('Sending HTTP request with order id: '.$this->getOrderId().' and trader order id: '.$this->getTraderOrderId(), [
                'url' => $this->webhookUrl,
                'method' => strtoupper($this->httpVerb),
                'headers' => $this->headers,
                'payload' => $this->payload,
                'order_id' => $this->getOrderId(),
                'trader_order_id' => $this->getTraderOrderId(),
                'attempt' => $this->attempts(),
            ]);

            // Make the HTTP request with detailed logging
            $response = Http::withHeaders($this->headers)
                ->timeout($this->requestTimeout)
                ->withOptions([
                    'verify' => $this->verifySsl,
                ])
                ->{$this->httpVerb}($this->webhookUrl, $this->payload);

            // Log successful response
            $this->logHttpResponse($response, true);

            // Check if response is successful (2xx status codes)
            if ($response->successful()) {
                Log::channel(LOG_CHANNEL_WEBHOOKS)->info('Webhook HTTP request completed successfully with order id: '.$this->getOrderId().' and trader order id: '.$this->getTraderOrderId(), [
                    'webhook_url' => $this->webhookUrl,
                    'status_code' => $response->status(),
                    'attempt' => $this->attempts(),
                    'order_id' => $this->getOrderId(),
                    'trader_order_id' => $this->getTraderOrderId(),
                ]);

                return;
            }

            // Handle non-2xx responses
            $this->handleFailedResponse($response);
        } catch (Exception $e) {
            $this->logHttpException($e);

            // Re-throw to trigger retry mechanism
            throw $e;
        }
    }

    /**
     * Log HTTP response details
     */
    private function logHttpResponse(Response $response, bool $isSuccess = true): void
    {
        $logLevel = $isSuccess ? 'info' : 'warning';

        Log::channel(LOG_CHANNEL_WEBHOOKS)->{$logLevel}('HTTP webhook response received with order id: '.$this->getOrderId().' and trader order id: '.$this->getTraderOrderId(), [
            'webhook_url' => $this->webhookUrl,
            'status_code' => $response->status(),
            'status_text' => $response->reason(),
            'response_headers' => $response->headers(),
            'response_body' => $this->truncateResponseBody($response->body()),
            'response_size' => strlen($response->body()),
            'attempt' => $this->attempts(),
            'is_success' => $isSuccess,
            'order_id' => $this->getOrderId(),
            'trader_order_id' => $this->getTraderOrderId(),
        ]);
    }

    /**
     * Handle failed HTTP responses
     */
    private function handleFailedResponse(Response $response): void
    {
        Log::channel(LOG_CHANNEL_WEBHOOKS)->error('Webhook HTTP request failed with order id: '.$this->getOrderId().' and trader order id: '.$this->getTraderOrderId(), [
            'webhook_url' => $this->webhookUrl,
            'status_code' => $response->status(),
            'status_text' => $response->reason(),
            'response_headers' => $response->headers(),
            'response_body' => $this->truncateResponseBody($response->body()),
            'attempt' => $this->attempts(),
            'max_attempts' => $this->tries,
            'order_id' => $this->getOrderId(),
            'trader_order_id' => $this->getTraderOrderId(),
        ]);

        // Throw exception to trigger retry mechanism
        throw new Exception("Webhook failed permanently with order id: {$this->getOrderId()} and trader order id: {$this->getTraderOrderId()} with status {$response->status()}: {$response->reason()}");
    }

    /**
     * Log HTTP exceptions
     */
    private function logHttpException(Exception $e): void
    {
        Log::channel(LOG_CHANNEL_WEBHOOKS)->error('Webhook HTTP request exception with order id: '.$this->getOrderId().' and trader order id: '.$this->getTraderOrderId(), [
            'webhook_url' => $this->webhookUrl,
            'exception_class' => get_class($e),
            'exception_message' => $e->getMessage(),
            'exception_code' => $e->getCode(),
            'exception_file' => $e->getFile(),
            'exception_line' => $e->getLine(),
            'attempt' => $this->attempts(),
            'max_attempts' => $this->tries,
            'order_id' => $this->getOrderId(),
            'trader_order_id' => $this->getTraderOrderId(),
        ]);
    }

    /**
     * Truncate response body for logging to prevent excessive log sizes
     */
    private function truncateResponseBody(string $body, int $maxLength = 1000): string
    {
        if (strlen($body) <= $maxLength) {
            return $body;
        }

        return substr($body, 0, $maxLength).'... [truncated]';
    }

    /**
     * Handle job failure (when all retries are exhausted)
     */
    public function failed(\Throwable $exception): void
    {
        Log::channel(LOG_CHANNEL_WEBHOOKS)->critical('Webhook HTTP request permanently failed with order id: '.$this->getOrderId().' and trader order id: '.$this->getTraderOrderId(), [
            'webhook_url' => $this->webhookUrl,
            'total_attempts' => $this->attempts(),
            'max_attempts' => $this->tries,
            'final_exception_class' => get_class($exception),
            'final_exception_message' => $exception->getMessage(),
            'payload' => $this->payload,
            'order_id' => $this->getOrderId(),
            'trader_order_id' => $this->getTraderOrderId(),
        ]);

        // Call parent failed method
        parent::failed($exception);
    }

    private function getOrderId(): ?int
    {
        return $this->payload['order_id'] ?? null;
    }

    private function getTraderOrderId(): ?int
    {
        return $this->payload['trading_information']['trading_id'] ?? null;
    }

    /**
     * @return array<string, string>
     */
    public function testUnusedFunction(): array
    {
        return [
            'test' => 'test',
        ];
    }
}
