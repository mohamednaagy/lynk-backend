<?php

namespace Modules\Otpify\Exceptions;

use App\Enums\ErrorCode;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class OtpCodeAlreadyUsedException extends Exception
{
    /**
     * Render the exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function render(Request $request)
    {
        $message = trans('otpify::response.otp_already_used');
        $code = Response::HTTP_BAD_REQUEST;

        if ($request->expectsJson()) {
            return response()->errorResponse(
                $message,
                $code,
                ErrorCode::OTPIFY_ALREADY_USED
            );
        }

        abort($code, $message);
    }
}
