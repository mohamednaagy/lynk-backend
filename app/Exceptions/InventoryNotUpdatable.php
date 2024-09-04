<?php

namespace App\Exceptions;

use App\Enums\ErrorCode;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class InventoryNotUpdatable extends Exception
{
    /**
     * Render the exception into an HTTP response.
     *
     * @return \Illuminate\Http\Response
     */
    public function render(Request $request)
    {
        $message =  __('error.inventory_cannot_be_updated');
        $code = Response::HTTP_BAD_REQUEST;

        if ($request->expectsJson()) {
            return response()->errorResponse(
                $message,
                $code,
                ErrorCode::INVENTORY_NOT_UPDATABLE
            );
        }

        abort($code, $message);
    }
}
