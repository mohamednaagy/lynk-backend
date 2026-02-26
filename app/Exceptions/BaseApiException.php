<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

abstract class BaseApiException extends Exception
{
    protected int $httpStatus = Response::HTTP_BAD_REQUEST;

    abstract protected function errorCode(): int;

    protected function errorMessage(): string
    {
        return $this->getMessage();
    }

    public function render(Request $request): JsonResponse
    {
        return response()->errorResponse(
            $this->errorMessage(),
            $this->httpStatus,
            $this->errorCode()
        );
    }
}
