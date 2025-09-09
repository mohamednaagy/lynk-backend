<?php

namespace App\Support\Webhooks\Jobs;

use Exception;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Spatie\WebhookServer\CallWebhookJob;

class LoggingWebhookJob extends CallWebhookJob
{
    public function handle()
    {
        $startTime = microtime(true);

        Log::channel(LOG_CHANNEL_WEBHOOKS)->info('HTTP webhook request initiated', [
            'webhook_url' => $this->webhookUrl,
            'http_verb' => $this->httpVerb,
            'headers' => $this->headers,
            'payload_size' => strlen(json_encode($this->payload)),
            'timeout' => $this->requestTimeout,
            'attempt' => $this->attempts(),
        ]);

        try {
            // Log the outgoing request details
            Log::channel(LOG_CHANNEL_WEBHOOKS)->debug('Sending HTTP request', [
                'url' => $this->webhookUrl,
                'method' => strtoupper($this->httpVerb),
                'headers' => $this->headers,
                'payload' => $this->payload,
                'attempt' => $this->attempts(),
            ]);

            // Make the HTTP request with detailed logging
            $response = Http::withHeaders($this->headers)
                ->timeout($this->requestTimeout)
                ->withOptions([
                    'verify' => $this->verifySsl,
                ])
                ->{$this->httpVerb}($this->webhookUrl, $this->payload);

            $endTime = microtime(true);
            $duration = round(($endTime - $startTime) * 1000, 2); // Duration in milliseconds

            // Log successful response
            $this->logHttpResponse($response, $duration, true);

            // Check if response is successful (2xx status codes)
            if ($response->successful()) {
                Log::channel(LOG_CHANNEL_WEBHOOKS)->info('Webhook HTTP request completed successfully', [
                    'webhook_url' => $this->webhookUrl,
                    'status_code' => $response->status(),
                    'duration_ms' => $duration,
                    'attempt' => $this->attempts(),
                ]);

                return;
            }

            // Handle non-2xx responses
            $this->handleFailedResponse($response, $duration);

        } catch (Exception $e) {
            $endTime = microtime(true);
            $duration = round(($endTime - $startTime) * 1000, 2);

            $this->logHttpException($e, $duration);

            // Re-throw to trigger retry mechanism
            throw $e;
        }
    }

    /**
     * Log HTTP response details
     */
    private function logHttpResponse(Response $response, float $duration, bool $isSuccess = true): void
    {
        $logLevel = $isSuccess ? 'info' : 'warning';

        Log::channel(LOG_CHANNEL_WEBHOOKS)->{$logLevel}('HTTP webhook response received', [
            'webhook_url' => $this->webhookUrl,
            'status_code' => $response->status(),
            'status_text' => $response->reason(),
            'duration_ms' => $duration,
            'response_headers' => $response->headers(),
            'response_body' => $this->truncateResponseBody($response->body()),
            'response_size' => strlen($response->body()),
            'attempt' => $this->attempts(),
            'is_success' => $isSuccess,
        ]);
    }

    /**
     * Handle failed HTTP responses
     */
    private function handleFailedResponse(Response $response, float $duration): void
    {
        Log::channel(LOG_CHANNEL_WEBHOOKS)->error('Webhook HTTP request failed', [
            'webhook_url' => $this->webhookUrl,
            'status_code' => $response->status(),
            'status_text' => $response->reason(),
            'duration_ms' => $duration,
            'response_headers' => $response->headers(),
            'response_body' => $this->truncateResponseBody($response->body()),
            'attempt' => $this->attempts(),
            'max_attempts' => $this->tries,
        ]);

        // Throw exception to trigger retry mechanism
        throw new Exception("Webhook failed with status {$response->status()}: {$response->reason()}");
    }

    /**
     * Log HTTP exceptions
     */
    private function logHttpException(Exception $e, float $duration): void
    {
        Log::channel(LOG_CHANNEL_WEBHOOKS)->error('Webhook HTTP request exception', [
            'webhook_url' => $this->webhookUrl,
            'duration_ms' => $duration,
            'exception_class' => get_class($e),
            'exception_message' => $e->getMessage(),
            'exception_code' => $e->getCode(),
            'exception_file' => $e->getFile(),
            'exception_line' => $e->getLine(),
            'attempt' => $this->attempts(),
            'max_attempts' => $this->tries,
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
    public function failed(\Throwable $exception)
    {
        Log::channel(LOG_CHANNEL_WEBHOOKS)->critical('Webhook HTTP request permanently failed', [
            'webhook_url' => $this->webhookUrl,
            'total_attempts' => $this->attempts(),
            'max_attempts' => $this->tries,
            'final_exception_class' => get_class($exception),
            'final_exception_message' => $exception->getMessage(),
            'payload' => $this->payload,
        ]);

        // Call parent failed method if it exists
        if (method_exists(parent::class, 'failed')) {
            parent::failed($exception);
        }
    }
}
