<?php

namespace App\Support\Webhooks\Exceptions;

use App\Enums\ErrorCode;
use Exception;
use Illuminate\Http\Response;

class WebhookTypeNotFoundException extends Exception
{
    public function render($request)
    {
        $message = __('error.webhook_type_not_supported');
        $code = Response::HTTP_BAD_REQUEST;

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
