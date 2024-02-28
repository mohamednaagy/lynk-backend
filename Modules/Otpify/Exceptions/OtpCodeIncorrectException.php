<?php

namespace Modules\Otpify\Exceptions;

use App\Enums\ErrorCode;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class OtpCodeIncorrectException extends Exception
{
    /**
     * Render the exception into an HTTP response.
     *
     * @return \Illuminate\Http\Response
     */
    public function render(Request $request)
    {
        $message = trans('otpify::response.otp_code_invalid');
        $code = Response::HTTP_BAD_REQUEST;

        if ($request->expectsJson()) {
            return response()->errorResponse(
                $message,
                $code,
                ErrorCode::OTPIFY_CODE_INVALID
            );
        }

        abort($code, $message);
    }
}
