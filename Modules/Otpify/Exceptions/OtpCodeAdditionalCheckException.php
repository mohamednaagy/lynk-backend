<?php

namespace Modules\Otpify\Exceptions;

use App\Enums\ErrorCode;
use Exception;
use Illuminate\Http\Response;

class OtpCodeAdditionalCheckException extends Exception
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
            trans('otpify::response.otp_code_additional_check_error'),
            Response::HTTP_UNAUTHORIZED,
            ErrorCode::OTPIFY_ADDITIONAL_CHECK
        );
    }
}
