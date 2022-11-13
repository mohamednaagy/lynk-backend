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

    public function outputFromHtml($html, $path, $options = [])
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
                throw new MissingStorageCallbackException();
            }

            $response = Http::baseUrl($this->baseUrl)
                ->withOptions([
                    'sink' => $tmpFileResource,
                ])
                ->post('pdf', $this->prepareRequestData($html, $options));

            if (! $response->ok()) {
                throw new GeneratingPdfException([
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
            }

            $storageCallback($tmpFileResource);

            fclose($tmpFileResource);
        } catch (\Throwable $th) {
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
