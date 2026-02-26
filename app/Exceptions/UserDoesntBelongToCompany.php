<?php

namespace App\Exceptions;

use App\Enums\ErrorCode;
use Illuminate\Http\Response;

class UserDoesntBelongToCompany extends BaseApiException
{
    protected int $httpStatus = Response::HTTP_UNPROCESSABLE_ENTITY;

    protected function errorCode(): int
    {
        return ErrorCode::USER_DOESNT_BELONG_TO_COMPANY;
    }

    protected function errorMessage(): string
    {
        return trans('error.user_doesnt_belong_to_company');
    }
}
