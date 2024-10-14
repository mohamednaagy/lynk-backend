<?php

namespace App\Exceptions;

use App\Enums\ErrorCode;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class UserDoesntBelongToCompany extends Exception
{
    /**
     * Render the exception into an HTTP response.
     *
     * @return \Illuminate\Http\Response
     */
    public function render(Request $request)
    {
        $message = trans('error.user_doesnt_belong_to_company');
        $code = Response::HTTP_UNPROCESSABLE_ENTITY;

        if ($request->expectsJson()) {
            return response()->errorResponse(
                $message,
                $code,
                ErrorCode::USER_DOESNT_BELONG_TO_COMPANY
            );
        }

        abort($code, $message);
    }
}
