<?php

namespace App\Support\Webhooks\Exceptions;

use App\Enums\ErrorCode;
use Exception;
use Illuminate\Http\Response;

class WebhookTypeNotFoundException extends Exception
{
    public function render($request)
    {
        $message = __('Unsupported webhook type');
        $code = Response::HTTP_NOT_FOUND;

        if ($request->expectsJson()) {
            return response()->errorResponse(
                $message,
                $code,
                ErrorCode::WEBHOOK_LIMIT_TYPE_NOT_FOUND
            );
        }

        abort($code, $message);
    }
}
