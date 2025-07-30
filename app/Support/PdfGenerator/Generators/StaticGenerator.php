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
            'default_font' => 'dejavusans',
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
            // Increase memory limit for PDF generation
            $originalMemoryLimit = ini_get('memory_limit');
            ini_set('memory_limit', '512M');

            // Create new mPDF instance for each generation
            $mpdf = $this->createMpdfInstance();

            // Ensure proper UTF-8 encoding for Arabic text
            $html = $this->prepareHtmlForArabic($html);

            // Apply options to mPDF instance
            $this->applyMpdfOptions($mpdf, $options);

            // Write HTML to mPDF with error suppression for CSS issues
            try {
                $mpdf->WriteHTML($html);
            } catch (Throwable $cssError) {
                // If CSS error occurs, try with minimal styling
                Log::channel('lynk')->warning('CSS Error in mPDF, trying with minimal styling', [
                    'error' => $cssError->getMessage(),
                    'request_id' => $this->requestId,
                ]);

                // Create a new instance and try with stripped HTML
                $mpdf = $this->createMpdfInstance();
                $this->applyMpdfOptions($mpdf, $options);
                $minimalHtml = $this->stripToMinimalHtml($html);
                $mpdf->WriteHTML($minimalHtml);
            }

            // Get PDF content
            $result = $mpdf->Output('', 'S');

            // Restore original memory limit
            ini_set('memory_limit', $originalMemoryLimit);

            return $result;
        } catch (Throwable $e) {
            // Restore original memory limit in case of error
            if (isset($originalMemoryLimit)) {
                ini_set('memory_limit', $originalMemoryLimit);
            }

            Log::channel('lynk')->error('mPDF Generation Error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_id' => $this->requestId,
                'memory_limit' => ini_get('memory_limit'),
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

    /**
     * Prepare HTML for proper Arabic text rendering and CSS compatibility
     */
    protected function prepareHtmlForArabic(string $html): string
    {
        // Ensure proper UTF-8 encoding
        if (! mb_check_encoding($html, 'UTF-8')) {
            $html = mb_convert_encoding($html, 'UTF-8', 'auto');
        }

        // Add proper meta charset if not present
        if (! str_contains($html, '<meta charset="utf-8"')) {
            $html = str_replace('<head>', '<head><meta charset="utf-8">', $html);
        }

        // Add RTL support for Arabic text
        if (! str_contains($html, 'dir="rtl"') && preg_match('/[\x{0600}-\x{06FF}]/u', $html)) {
            $html = str_replace('<body>', '<body dir="rtl">', $html);
        }

        // Add Arabic font support
        if (preg_match('/[\x{0600}-\x{06FF}]/u', $html)) {
            $html = str_replace('<head>', '<head><style>body { font-family: "DejaVu Sans", "Arial Unicode MS", sans-serif; }</style>', $html);
        }

        // Clean up CSS that mPDF doesn't support
        $html = $this->cleanupCssForMpdf($html);

        return $html;
    }

    /**
     * Clean up CSS for mPDF compatibility
     */
    protected function cleanupCssForMpdf(string $html): string
    {
        // Remove CSS variables (var(--variable))
        $html = preg_replace('/var\([^)]+\)/', '', $html);

        // Replace modern CSS color formats with basic ones
        $html = preg_replace('/rgb\([^)]*\/[^)]*\)/', 'rgb(0, 0, 0)', $html); // rgb with alpha
        $html = preg_replace('/rgba\([^)]*\)/', 'rgb(0, 0, 0)', $html); // rgba
        $html = preg_replace('/hsl\([^)]*\)/', 'rgb(0, 0, 0)', $html); // hsl
        $html = preg_replace('/hsla\([^)]*\)/', 'rgb(0, 0, 0)', $html); // hsla

        // Handle the specific error: rgb(0 0 0 / var...)
        $html = preg_replace('/rgb\([^)]*\/\s*var[^)]*\)/', 'rgb(0, 0, 0)', $html);

        // Remove unsupported CSS properties
        $unsupportedProperties = [
            'backdrop-filter',
            'filter',
            'transform',
            'transition',
            'animation',
            'box-shadow',
            'text-shadow',
            'border-radius',
            'opacity',
            'z-index',
            'position: fixed',
            'position: sticky',
        ];

        foreach ($unsupportedProperties as $property) {
            $html = preg_replace('/'.preg_quote($property, '/').'\s*:[^;]+;?/', '', $html);
        }

        // Remove CSS calc() functions
        $html = preg_replace('/calc\([^)]+\)/', '0', $html);

        // Remove CSS custom properties
        $html = preg_replace('/--[a-zA-Z0-9-]+:\s*[^;]+;?/', '', $html);

        // Remove @media queries
        $html = preg_replace('/@media[^{]*\{[^}]*\}/s', '', $html);

        // Remove @keyframes
        $html = preg_replace('/@keyframes[^{]*\{[^}]*\}/s', '', $html);

        // Remove @import
        $html = preg_replace('/@import[^;]+;?/', '', $html);

        // Remove @font-face
        $html = preg_replace('/@font-face[^{]*\{[^}]*\}/s', '', $html);

        return $html;
    }

    /**
     * Strip HTML to minimal version for mPDF compatibility
     */
    protected function stripToMinimalHtml(string $html): string
    {
        // Remove all style tags
        $html = preg_replace('/<style[^>]*>.*?<\/style>/s', '', $html);

        // Remove all script tags
        $html = preg_replace('/<script[^>]*>.*?<\/script>/s', '', $html);

        // Remove all CSS classes
        $html = preg_replace('/\s+class\s*=\s*["\'][^"\']*["\']/', '', $html);

        // Remove all style attributes
        $html = preg_replace('/\s+style\s*=\s*["\'][^"\']*["\']/', '', $html);

        // Remove all data attributes
        $html = preg_replace('/\s+data-[^=]*\s*=\s*["\'][^"\']*["\']/', '', $html);

        // Keep only basic HTML structure
        $html = strip_tags($html, '<html><head><body><div><p><h1><h2><h3><h4><h5><h6><table><tr><td><th><ul><ol><li><strong><em><b><i><br><hr>');

        // Add basic styling for readability
        $html = str_replace('<head>', '<head><style>
            body { font-family: Arial, sans-serif; font-size: 12px; line-height: 1.4; }
            h1, h2, h3, h4, h5, h6 { margin: 10px 0; }
            p { margin: 5px 0; }
            table { border-collapse: collapse; width: 100%; }
            th, td { border: 1px solid #ccc; padding: 5px; }
        </style>', $html);

        return $html;
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
