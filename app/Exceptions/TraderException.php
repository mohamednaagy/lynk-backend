<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Support\Collection;
use Throwable;

class TraderException extends Exception
{
    public function __construct(Collection $exceptionData, string $message = '', int $code = 0, ?Throwable $previous = null)
    {
        activity()
            ->withProperties($exceptionData)
            ->log($exceptionData->get('driver'));

        parent::__construct($exceptionData->map(function ($value, $key) {
            if (is_array($value) || is_object($value)) {
                $value = json_encode($value);
            }

            return $key.' : '.$value;
        })->join(PHP_EOL), $code, $previous);
    }
}
