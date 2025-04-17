<?php

namespace App\Support\PdfGenerator\Generators;

use App\Support\PdfGenerator\Contracts\GeneratorInterface;
use App\Support\PdfGenerator\Exceptions\GeneratingPdfException;
use App\Support\PdfGenerator\Exceptions\MissingStorageCallbackException;
use Closure;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class BrowserlessGenerator implements GeneratorInterface
{
    protected $baseUrl;

    protected $storageDisk;

    protected $html;

    protected $options;

    protected $maxRetries = 3;

    protected $retryDelay = 5000; // milliseconds

    protected $timeout = 120; // seconds

    public function __construct($options)
    {
        $this->baseUrl = $options['base_url'];
        $this->storageDisk = $options['storage_disk'];
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
            $storageCallback = null;

            if ($options instanceof Closure) {
                $storageCallback = $options;
                $options = [];
            } elseif (isset($options['storageCallback']) && $options['storageCallback'] instanceof Closure) {
                $storageCallback = $options['storageCallback'];
                unset($options['storageCallback']);
            } else {
                throw new MissingStorageCallbackException;
            }

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
            $this->cleanupTmpFile($tmpFileResource);
            $this->logFinalError($th, $attempt);
            throw $th;
        }
    }

    /**
     * Make HTTP request to generate PDF
     *
     * @param  resource  $tmpFileResource
     * @return \Illuminate\Http\Client\Response
     */
    protected function makeHttpRequest($tmpFileResource, string $html, array $options)
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

        Log::channel('local_market')->info('PDF Generation Success', [
            'attempt' => $attempt,
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
        Log::channel('local_market')->error('PDF Generation Failed', [
            'attempt' => $attempt,
            'status' => $response->status(),
            'body' => $response->body(),
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
        Log::channel('local_market')->info('PDF Generation Attempt', [
            'attempt' => $attempt,
            'max_retries' => $this->maxRetries,
            'url' => $this->baseUrl,
        ]);
    }

    /**
     * Log retryable error
     */
    protected function logRetryableError(Throwable $e, int $attempt)
    {
        Log::channel('local_market')->error('PDF Generation Retryable Error', [
            'attempt' => $attempt,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);
    }

    /**
     * Log final error
     */
    protected function logFinalError(Throwable $th, int $attempt)
    {
        Log::channel('local_market')->error('PDF Generation Final Error', [
            'error_message' => $th->getMessage(),
            'stack_trace' => $th->getTraceAsString(),
            'attempts' => $attempt,
        ]);
    }

    protected function getDefaultOptions()
    {
        return $this->options;
    }

    public function getStorageDisk()
    {
        return $this->storageDisk;
    }

    public function prepareRequestData($html, $options)
    {
        return [
            'html' => $html,
            'gotoOptions' => ['waitUntil' => 'networkidle0'],
            'options' => array_merge($this->getDefaultOptions(), $options),
        ];
    }
}
