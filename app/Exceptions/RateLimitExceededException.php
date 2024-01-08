<?php

namespace App\Exceptions;

use Exception;

class RateLimitExceededException extends Exception
{
    protected $context = [];

    public function __construct($key, \Throwable $previous = null)
    {
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
