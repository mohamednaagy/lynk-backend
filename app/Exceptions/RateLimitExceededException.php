<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Support\Facades\Log;

class RateLimitExceededException extends Exception
{
    protected $context = [];

    public function __construct($key, ?\Throwable $previous = null)
    {
        Log::channel('bursam')->error('Rate limit exceeded for key:', $this->context);
        parent::__construct('Rate limit exceeded for key: '.$key, 0, $previous);
    }

    public function context()
    {
        return $this->context;
    }

    public function setContext(array $context)
    {
        $this->context = $context;
    }
}
