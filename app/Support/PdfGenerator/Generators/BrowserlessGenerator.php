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
    protected $baseUrl;

    protected $storageDisk;

    protected $options;

    protected $maxRetries = 3;

    protected $retryDelay = 5000; // milliseconds

    protected $timeout = 120; // seconds

    protected $requestId;

    public function __construct($options)
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
     *
     * @throws GeneratingPdfException
     * @throws MissingStorageCallbackException
     * @throws Throwable
     */
    public function outputFromHtml($html, $options = [])
    {
        $tmpFileResource = null;
        $attempt = 0;

        try {
            [$options, $storageCallback] = $this->resolveStorageCallback($options);

            while ($attempt < $this->maxRetries) {
                $attempt++;
                $tmpFileResource = tmpfile();

                try {
                    $this->logAttempt($attempt);

                    $response = $this->makeHttpRequest($tmpFileResource, $html, $options);

                    if ($response->ok()) {
                        return $this->handleSuccessfulResponse($tmpFileResource, $storageCallback, $attempt);
                    }

                    $this->handleFailedResponse($response, $attempt);

                    if ($attempt < $this->maxRetries) {
                        $this->waitBeforeRetry();

                        continue;
                    }

                    throw new GeneratingPdfException([
                        'status' => $response->status(),
                        'body' => $response->body(),
                        'attempts' => $attempt,
                        'request_id' => $this->requestId,
                    ]);
                } catch (Throwable $e) {
                    $this->cleanupTmpFile($tmpFileResource);

                    if ($attempt < $this->maxRetries) {
                        $this->logRetryableError($e, $attempt);
                        $this->waitBeforeRetry();

                        continue;
                    }

                    $this->logFinalError($e, $attempt);
                    throw $e;
                }
            }
        } catch (Throwable $th) {
            $this->logFinalError($th, $attempt);
            throw $th;
        }
    }

    /**
     * Resolve storage callback from options
     *
     * @param  array|Closure  &$options
     * @return array
     *
     * @throws MissingStorageCallbackException
     */
    protected function resolveStorageCallback(&$options)
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

    /**
     * Make HTTP request to generate PDF
     *
     * @param  resource  $tmpFileResource
     * @return \Illuminate\Http\Client\Response
     */
    protected function makeHttpRequest($tmpFileResource, string $html, array|Closure $options)
    {
        return Http::timeout($this->timeout)
            ->baseUrl($this->baseUrl)
            ->withOptions(['sink' => $tmpFileResource])
            ->post('pdf', $this->prepareRequestData($html, $options));
    }

    /**
     * Handle successful PDF generation response
     *
     * @param  resource  $tmpFileResource
     * @return mixed
     */
    protected function handleSuccessfulResponse($tmpFileResource, Closure $storageCallback, int $attempt)
    {
        $storedFile = $storageCallback($tmpFileResource);
        $this->cleanupTmpFile($tmpFileResource);

        Log::channel('lynk')->info('PDF Generation Success', [
            'attempt' => $attempt,
            'request_id' => $this->requestId,
        ]);

        return $storedFile;
    }

    /**
     * Handle failed PDF generation response
     *
     * @param  \Illuminate\Http\Client\Response  $response
     */
    protected function handleFailedResponse($response, int $attempt)
    {
        Log::channel('lynk')->error('PDF Generation Failed', [
            'attempt' => $attempt,
            'status' => $response->status(),
            'body' => $response->body(),
            'request_id' => $this->requestId,
        ]);
    }

    /**
     * Wait before retrying
     */
    protected function waitBeforeRetry()
    {
        usleep($this->retryDelay * 1000);
    }

    /**
     * Clean up temporary file
     *
     * @param  resource|null  $tmpFileResource
     */
    protected function cleanupTmpFile($tmpFileResource)
    {
        if (is_resource($tmpFileResource)) {
            fclose($tmpFileResource);
        }
    }

    /**
     * Log attempt information
     */
    protected function logAttempt(int $attempt)
    {
        Log::channel('lynk')->info('PDF Generation Attempt', [
            'attempt' => $attempt,
            'max_retries' => $this->maxRetries,
            'url' => $this->baseUrl,
            'request_id' => $this->requestId,
        ]);
    }

    /**
     * Log retryable error
     */
    protected function logRetryableError(Throwable $e, int $attempt)
    {
        Log::channel('lynk')->error('PDF Generation Retryable Error', [
            'attempt' => $attempt,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
            'request_id' => $this->requestId,
        ]);
    }

    /**
     * Log final error
     */
    protected function logFinalError(Throwable $th, int $attempt)
    {
        Log::channel('lynk')->error('PDF Generation Final Error', [
            'error_message' => $th->getMessage(),
            'stack_trace' => $th->getTraceAsString(),
            'attempts' => $attempt,
            'request_id' => $this->requestId,
        ]);
    }

    /**
     * Get default PDF generation options
     *
     * @return array
     */
    protected function getDefaultOptions()
    {
        return $this->options;
    }

    /**
     * Get storage disk
     *
     * @return mixed
     */
    public function getStorageDisk()
    {
        return $this->storageDisk;
    }

    /**
     * Prepare request data for PDF generation
     *
     * @param  string  $html
     * @param  array  $options
     * @return array
     */
    public function prepareRequestData($html, $options)
    {
        return [
            'html' => $html,
            'gotoOptions' => ['waitUntil' => 'networkidle0'],
            'options' => array_merge($this->getDefaultOptions(), $options),
        ];
    }
}
