<?php

namespace App\Support\PdfGenerator\Generators;

use App\Support\PdfGenerator\Contracts\GeneratorInterface;
use App\Support\PdfGenerator\Exceptions\GeneratingPdfException;
use App\Support\PdfGenerator\Exceptions\MissingStorageCallbackException;
use Closure;
use Illuminate\Support\Facades\Http;

class BrowserlessGenerator implements GeneratorInterface
{
    protected $baseUrl;

    protected $storageDisk;

    protected $html;

    protected $options;

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

    public function outputFromHtml($html, $options = [])
    {
        $tmpFileResource = tmpfile();

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

            $response = Http::retry(3, 100)->timeout(120)->baseUrl($this->baseUrl)
                ->withOptions([
                    'sink' => $tmpFileResource,
                ])
                ->post('pdf', $this->prepareRequestData($html, $options));

            if (! $response->ok()) {
                \Illuminate\Support\Facades\Log::channel('local_market')->error('tmpFile error 1', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                throw new GeneratingPdfException([
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
            }
            $storedFile = $storageCallback($tmpFileResource);

            fclose($tmpFileResource);

            \Illuminate\Support\Facades\Log::channel('local_market')->info('tmpFile success');

            return $storedFile;
        } catch (\Throwable $th) {
            \Illuminate\Support\Facades\Log::channel('local_market')->error('tmpFile error 2', [
                'error_message' => $th->getMessage(),
                'stack_trace' => $th->getTraceAsString(),
            ]);
            fclose($tmpFileResource);
            throw $th;
        }
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
