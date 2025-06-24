<?php

namespace App\Support\PdfGenerator\Generators;

use App\Support\PdfGenerator\Contracts\GeneratorInterface;
use App\Support\PdfGenerator\Exceptions\GeneratingPdfException;
use App\Support\PdfGenerator\Exceptions\MissingStorageCallbackException;
use Closure;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class BrowserlessGenerator implements GeneratorInterface
{
    protected string $baseUrl;
    protected string $storageDisk;
    protected array $options;
    protected int $maxRetries = 5;
    protected int $retryBaseDelaySeconds = 5;
    protected int $timeout = 120; // seconds
    protected string $requestId;

    public function __construct(array $options)
    {
        $this->baseUrl = $options['base_url'];
        $this->storageDisk = $options['storage_disk'];
        $this->requestId = (string) Str::uuid();
        unset($options['storage_disk'], $options['base_url']);

        $this->options = array_merge([
            'format' => 'A4',
            'printBackground' => true,
            'margin' => [
                'top' => '25',
                'bottom' => '25',
                'left' => '25',
                'right' => '25',
            ],
        ], $options);
    }

    /**
     * Generate PDF from HTML with retry mechanism
     *
     * @param  string  $html
     * @param  array|Closure  $options
     * @return mixed
     * @throws Throwable
     */
    public function outputFromHtml($html, $options)
    {
        $attempt = 1;

        try {
            [$options, $storageCallback] = $this->resolveStorageCallback($options);

            while ($attempt <= $this->maxRetries) {

                $tmpFileResource = tmpfile();
                if ($tmpFileResource === false) {
                    throw new \RuntimeException("Failed to create temporary file.");
                }

                try {
                    $this->logAttempt($attempt);

                    $response = $this->makeHttpRequest($tmpFileResource, $html, $options);

                    if ($response->ok()) {
                        return $this->handleSuccessfulResponse($tmpFileResource, $storageCallback, $attempt);
                    }

                    $this->handleFailedResponse($response, $attempt);

                    if ($attempt <= $this->maxRetries) {
                        $this->waitBeforeRetry($attempt);
                    } else {
                        throw new GeneratingPdfException([
                            'status' => $response->status(),
                            'body' => $response->body(),
                            'attempts' => $attempt,
                            'request_id' => $this->requestId,
                        ]);
                    }
                } catch (Throwable $e) {
                    $this->logRetryableError($e, $attempt);

                    if ($attempt <= $this->maxRetries) {
                        $this->waitBeforeRetry($attempt);
                    } else {
                        $this->logFinalError($e, $attempt);
                        throw $e;
                    }
                } finally {
                    $this->cleanupTmpFile($tmpFileResource);
                }

                $attempt++;
            }
        } catch (Throwable $th) {
            $this->logFinalError($th, $attempt);
            throw $th;
        }
    }

    protected function resolveStorageCallback(array|Closure &$options): array
    {
        if ($options instanceof Closure) {
            return [[], $options];
        }

        if (isset($options['storageCallback']) && $options['storageCallback'] instanceof Closure) {
            $storageCallback = $options['storageCallback'];
            unset($options['storageCallback']);
            return [$options, $storageCallback];
        }

        throw new MissingStorageCallbackException;
    }

    protected function makeHttpRequest($tmpFileResource, string $html, array $options)
    {
        return Http::timeout($this->timeout)
            ->baseUrl($this->baseUrl)
            ->withOptions(['sink' => $tmpFileResource])
            ->post('pdf', $this->prepareRequestData($html, $options));
    }

    protected function handleSuccessfulResponse($tmpFileResource, Closure $storageCallback, int $attempt): mixed
    {
        $storedFile = $storageCallback($tmpFileResource);

        Log::channel('lynk')->info('PDF Generation Success', [
            'attempt' => $attempt,
            'request_id' => $this->requestId,
        ]);

        return $storedFile;
    }

    protected function handleFailedResponse($response, int $attempt): void
    {
        Log::channel('lynk')->error('PDF Generation Failed', [
            'attempt' => $attempt,
            'status' => $response->status(),
            'body' => $response->body(),
            'request_id' => $this->requestId,
        ]);
    }

    protected function waitBeforeRetry(int $attempt): void
    {
        $seconds = $attempt * $this->retryBaseDelaySeconds;
        usleep($seconds * 1_000_000);
    }

    protected function cleanupTmpFile($tmpFileResource): void
    {
        if (is_resource($tmpFileResource)) {
            fclose($tmpFileResource);
        }
    }

    protected function logAttempt(int $attempt): void
    {
        Log::channel('lynk')->info('PDF Generation Attempt', [
            'attempt' => $attempt,
            'max_retries' => $this->maxRetries,
            'url' => $this->baseUrl,
            'request_id' => $this->requestId,
        ]);
    }

    protected function logRetryableError(Throwable $e, int $attempt): void
    {
        Log::channel('lynk')->error('PDF Generation Retryable Error', [
            'attempt' => $attempt,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
            'request_id' => $this->requestId,
        ]);
    }

    protected function logFinalError(Throwable $th, int $attempt): void
    {
        Log::channel('lynk')->error('PDF Generation Final Error', [
            'error_message' => $th->getMessage(),
            'stack_trace' => $th->getTraceAsString(),
            'attempts' => $attempt,
            'request_id' => $this->requestId,
        ]);
    }

    protected function getDefaultOptions(): array
    {
        return $this->options;
    }

    public function getStorageDisk(): string
    {
        return $this->storageDisk;
    }

    public function prepareRequestData(string $html, array $options): array
    {
        return [
            'html' => $html,
            'gotoOptions' => ['waitUntil' => 'networkidle0'],
            'options' => array_replace_recursive($this->getDefaultOptions(), $options),
        ];
    }
}
