<?php

namespace Modules\Otpify\Exceptions;

use App\Enums\ErrorCode;
use Exception;
use Illuminate\Http\Response;

class OtpCodeIncorrectException extends Exception
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
            trans('otpify::response.otp_code_invalid'),
            Response::HTTP_UNAUTHORIZED,
            ErrorCode::OTPIFY_CODE_INVALID
        );
    }
}
