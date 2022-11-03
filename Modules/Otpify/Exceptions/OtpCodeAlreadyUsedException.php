<?php

namespace Modules\Otpify\Exceptions;

use App\Enums\ErrorCode;
use Exception;
use Illuminate\Http\Response;

class OtpCodeAlreadyUsedException extends Exception
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
            trans('otpify::response.otp_already_used'),
            Response::HTTP_BAD_REQUEST,
            ErrorCode::OTPIFY_ALREADY_USED
        );
    }
}
