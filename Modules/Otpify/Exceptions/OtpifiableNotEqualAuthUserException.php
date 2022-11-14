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
        $message = trans('otpify::response.otpifiable_not_equal_auth_user');
        $code = Response::HTTP_UNAUTHORIZED;

        if ($request->expectsJson()) {
            return response()->errorResponse(
                $message,
                $code,
                ErrorCode::OTPIFY_WRONG_USER
            );
        }

        abort($code, $message);
    }
}
