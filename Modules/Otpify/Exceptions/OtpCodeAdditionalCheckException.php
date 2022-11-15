<?php

namespace Modules\Otpify\Exceptions;

use App\Enums\ErrorCode;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class OtpCodeAdditionalCheckException extends Exception
{
    /**
     * Render the exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function render(Request $request)
    {
        $message = trans('otpify::response.otp_code_additional_check_error');
        $code = Response::HTTP_UNAUTHORIZED;

        if ($request->expectsJson()) {
            return response()->errorResponse(
                $message,
                $code,
                ErrorCode::OTPIFY_ADDITIONAL_CHECK
            );
        }

        abort($code, $message);
    }
}
