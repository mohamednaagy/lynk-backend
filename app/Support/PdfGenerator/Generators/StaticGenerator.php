<?php

namespace App\Support\PdfGenerator\Generators;

use App\Support\PdfGenerator\Contracts\GeneratorInterface;
use App\Support\PdfGenerator\Exceptions\GeneratingPdfException;
use App\Support\PdfGenerator\Exceptions\MissingStorageCallbackException;
use Closure;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Omaralalwi\Gpdf\Gpdf;
use Throwable;

class StaticGenerator implements GeneratorInterface
{
    protected string $storageDisk;

    protected array $options;

    protected int $maxRetries = 3;

    protected int $retryBaseDelaySeconds = 2;

    protected string $requestId;

    protected Gpdf $gpdf;

    public function __construct(array $options)
    {
        $this->storageDisk = $options['storage_disk'];
        $this->requestId = (string) Str::uuid();
        unset($options['storage_disk']);

        $this->options = array_merge([
            'format' => 'A4',
            'margin' => [
                'top' => '25',
                'bottom' => '25',
                'left' => '25',
                'right' => '25',
            ],
            'printBackground' => true,
        ], $options);

        $this->gpdf = app(Gpdf::class);
    }

    /**
     * Generate PDF from HTML with retry mechanism
     *
     * @param  string  $html
     * @param  array|Closure  $options
     * @return mixed
     *
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
                    throw new \RuntimeException('Failed to create temporary file.');
                }

                try {
                    $this->logAttempt($attempt);

                    $pdfContent = $this->generatePdf($html, $options);

                    if ($pdfContent !== false) {
                        // Write PDF content to temporary file
                        fwrite($tmpFileResource, $pdfContent);
                        rewind($tmpFileResource);

                        return $this->handleSuccessfulResponse($tmpFileResource, $storageCallback, $attempt);
                    }

                    $this->handleFailedGeneration($attempt);

                    if ($attempt <= $this->maxRetries) {
                        $this->waitBeforeRetry($attempt);
                    } else {
                        throw new GeneratingPdfException([
                            'error' => 'Failed to generate PDF content',
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

    /**
     * Generate PDF using the omaralalwi/gpdf package
     */
    protected function generatePdf(string $html, array $options): string|false
    {
        try {
            $pdfOptions = $this->preparePdfOptions($options);

            return $this->gpdf->generate($html, $pdfOptions);
        } catch (Throwable $e) {
            Log::channel('lynk')->error('GPDF Generation Error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_id' => $this->requestId,
            ]);

            return false;
        }
    }

    /**
     * Prepare options for the GPDF library
     */
    protected function preparePdfOptions(array $options): array
    {
        $mergedOptions = array_replace_recursive($this->getDefaultOptions(), $options);

        // Convert options to GPDF format if needed
        $gpdfOptions = [
            'format' => $mergedOptions['format'] ?? 'A4',
            'margin' => $mergedOptions['margin'] ?? [
                'top' => '25',
                'bottom' => '25',
                'left' => '25',
                'right' => '25',
            ],
            'printBackground' => $mergedOptions['printBackground'] ?? true,
        ];

        // Add any additional GPDF specific options
        if (isset($mergedOptions['orientation'])) {
            $gpdfOptions['orientation'] = $mergedOptions['orientation'];
        }

        if (isset($mergedOptions['scale'])) {
            $gpdfOptions['scale'] = $mergedOptions['scale'];
        }

        return $gpdfOptions;
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

    protected function handleSuccessfulResponse($tmpFileResource, Closure $storageCallback, int $attempt): mixed
    {
        $storedFile = $storageCallback($tmpFileResource);

        Log::channel('lynk')->info('PDF Generation Success', [
            'attempt' => $attempt,
            'request_id' => $this->requestId,
            'generator' => 'StaticGenerator',
        ]);

        return $storedFile;
    }

    protected function handleFailedGeneration(int $attempt): void
    {
        Log::channel('lynk')->error('PDF Generation Failed', [
            'attempt' => $attempt,
            'error' => 'Failed to generate PDF content',
            'request_id' => $this->requestId,
            'generator' => 'StaticGenerator',
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
            'generator' => 'StaticGenerator',
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
            'generator' => 'StaticGenerator',
        ]);
    }

    protected function logFinalError(Throwable $th, int $attempt): void
    {
        Log::channel('lynk')->error('PDF Generation Final Error', [
            'error_message' => $th->getMessage(),
            'stack_trace' => $th->getTraceAsString(),
            'attempts' => $attempt,
            'request_id' => $this->requestId,
            'generator' => 'StaticGenerator',
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
}
