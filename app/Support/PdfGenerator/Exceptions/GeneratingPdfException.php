<?php

namespace App\Support\PdfGenerator\Exceptions;

use Exception;

class GeneratingPdfException extends Exception
{
    protected $error;

    public function __construct($error)
    {
        $this->error = $error;

        parent::__construct('Failed generating PDF');
    }

    public function context()
    {
        return [
            'error_type' => $this->error,
        ];
    }
}
