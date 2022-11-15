<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Support\Collection;
use Throwable;

class TraderException extends Exception
{
    public function __construct(Collection $exceptionData, string $message = '', int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);

        activity()
            ->withProperties($exceptionData)
            ->log($exceptionData->get('driver'));
    }
}
