<?php

namespace App\Support\PdfGenerator\Generators;

use App\Support\PdfGenerator\Contracts\GeneratorInterface;
use App\Support\PdfGenerator\Exceptions\GeneratingPdfException;
use App\Support\PdfGenerator\Exceptions\MissingStorageCallbackException;
use Closure;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Mpdf\Mpdf;
use Throwable;

class StaticGenerator implements GeneratorInterface
{
    protected string $storageDisk;

    protected array $options;

    protected int $maxRetries = 3;

    protected int $retryBaseDelaySeconds = 2;

    protected string $requestId;

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

        // mPDF instance will be created per generation to avoid conflicts
    }

    /**
     * Create mPDF instance with Arabic support
     */
    protected function createMpdfInstance(): Mpdf
    {
        $config = [
            'mode' => 'utf-8',
            'format' => 'A4',
            'margin_left' => 25,
            'margin_right' => 25,
            'margin_top' => 25,
            'margin_bottom' => 25,
            'margin_header' => 10,
            'margin_footer' => 10,
            'tempDir' => storage_path('app/tmp/mpdf'),
            'default_font' => 'ui-sans-serif',
            'default_font_size' => 10,
            'default_font_dir' => storage_path('fonts'),
            'default_font_cache_dir' => storage_path('fonts'),
            'autoScriptToLang' => true,
            'autoLangToFont' => true,
            'autoArabic' => true,
            'direction' => 'rtl',
        ];

        // Create temp directory if it doesn't exist
        if (! is_dir($config['tempDir'])) {
            mkdir($config['tempDir'], 0755, true);
        }

        return new Mpdf($config);
    }

    /**
     * Generate PDF from HTML with retry mechanism
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
     * Generate PDF using mPDF
     */
    protected function generatePdf(string $html, array $options): string|false
    {
        try {
            $start = microtime(true);
            // Create new mPDF instance for each generation
            $mpdf = $this->createMpdfInstance();

            // Apply options to mPDF instance
            $this->applyMpdfOptions($mpdf, $options);

            // Write HTML to mPDF
            $mpdf->WriteHTML($html);

            // Get PDF content
            $result = $mpdf->Output('', 'S');

            $end = microtime(true);
            $duration = $end - $start;
            Log::channel(LOG_CHANNEL_LYNK)->info('PDF Generation Duration - request_id => ' . $this->requestId, [
                'duration' => $duration,
                'request_id' => $this->requestId,
                'generator' => 'static',
            ]);

            return $result;
        } catch (Throwable $e) {
            Log::channel(LOG_CHANNEL_LYNK)->error('mPDF Generation Error - request_id => ' . $this->requestId, [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_id' => $this->requestId,
            ]);

            return false;
        }
    }

    /**
     * Apply options to mPDF instance
     */
    protected function applyMpdfOptions(Mpdf $mpdf, array $options): void
    {
        $mergedOptions = array_replace_recursive($this->getDefaultOptions(), $options);

        // Apply format
        if (isset($mergedOptions['format'])) {
            $mpdf->_setPageSize($mergedOptions['format'], $mpdf->DefOrientation);
        }

        // Apply margins
        if (isset($mergedOptions['margin'])) {
            $margin = $mergedOptions['margin'];
            $mpdf->SetMargins(
                $margin['left'] ?? 25,
                $margin['top'] ?? 25,
                $margin['right'] ?? 25
            );
            $mpdf->SetAutoPageBreak(true, $margin['bottom'] ?? 25);
        }

        // Apply orientation
        if (isset($mergedOptions['orientation'])) {
            $mpdf->DefOrientation = $mergedOptions['orientation'];
        }

        // Apply font
        if (isset($mergedOptions['font'])) {
            $mpdf->SetDefaultFont($mergedOptions['font']);
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

    protected function handleSuccessfulResponse($tmpFileResource, Closure $storageCallback, int $attempt): mixed
    {
        $storedFile = $storageCallback($tmpFileResource);

        Log::channel(LOG_CHANNEL_LYNK)->info('PDF Generation Success - request_id => ' . $this->requestId, [
            'attempt' => $attempt,
            'request_id' => $this->requestId,
            'generator' => 'StaticGenerator',
        ]);

        return $storedFile;
    }

    protected function handleFailedGeneration(int $attempt): void
    {
        Log::channel(LOG_CHANNEL_LYNK)->error('PDF Generation Failed - request_id => ' . $this->requestId, [
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
        Log::channel(LOG_CHANNEL_LYNK)->info('PDF Generation Attempt - request_id => ' . $this->requestId, [
            'attempt' => $attempt,
            'max_retries' => $this->maxRetries,
            'generator' => 'StaticGenerator',
            'request_id' => $this->requestId,
        ]);
    }

    protected function logRetryableError(Throwable $e, int $attempt): void
    {
        Log::channel(LOG_CHANNEL_LYNK)->error('PDF Generation Retryable Error - request_id => ' . $this->requestId, [
            'attempt' => $attempt,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
            'request_id' => $this->requestId,
            'generator' => 'StaticGenerator',
        ]);
    }

    protected function logFinalError(Throwable $th, int $attempt): void
    {
        Log::channel(LOG_CHANNEL_LYNK)->error('PDF Generation Final Error - request_id => ' . $this->requestId, [
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
}
