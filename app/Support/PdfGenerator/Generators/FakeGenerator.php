<?php

namespace App\Support\PdfGenerator\Generators;

use App\Support\PdfGenerator\Contracts\GeneratorInterface;
use App\Support\PdfGenerator\Exceptions\MissingStorageCallbackException;
use Closure;

class FakeGenerator implements GeneratorInterface
{
    protected $storageDisk;

    protected $html;

    protected $options = [];

    public function __construct()
    {
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
                throw new MissingStorageCallbackException();
            }

            $storedFile = $storageCallback($tmpFileResource);

            fclose($tmpFileResource);

            return $storedFile;
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
}
