<?php

namespace Modules\Otpify\Exceptions;

use App\Enums\ErrorCode;
use Exception;
use Illuminate\Http\Response;

class OtpifiableNotEqualAuthUserException extends Exception
{
    /**
     * Render the exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function render($request)
    {
        return response()->errorResponse(
            trans('otpify::response.otpifiable_not_equal_auth_user'),
            Response::HTTP_UNAUTHORIZED,
            ErrorCode::OTPIFY_WRONG_USER
        );
    }
}
