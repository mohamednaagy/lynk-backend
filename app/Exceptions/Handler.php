<?php

namespace App\Exceptions;

use App\Enums\ErrorCode;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;
use Stancl\Tenancy\Exceptions\TenantCouldNotBeIdentifiedByRequestDataException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * A list of exception types with their corresponding custom log levels.
     *
     * @var array<class-string<\Throwable>, \Psr\Log\LogLevel::*>
     */
    protected $levels = [
        //
    ];

    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<\Throwable>>
     */
    protected $dontReport = [
        //
    ];

    /**~
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     *
     * @return void
     */
    public function register()
    {
        $this->reportable(function (Throwable $e) {
            //
        });

        $this->renderable(function (TenantCouldNotBeIdentifiedByRequestDataException $e, $request) {
            return response()->errorResponse(trans('error.x_company_invalid'), code: ErrorCode::X_COMPANY_INVALID);
        });

        $this->renderable(function (NotFoundHttpException $e, $request) {
            $message = trans('error.item_not_found');
            $code = Response::HTTP_NOT_FOUND;

            return response()->errorResponse($message, $code, ErrorCode::ITEM_NOT_FOUND);
        });

        $this->renderable(function (ValidationException $e, $request) {
            return response()->json([
                'message' => __('validation.failed'),
                'errors' => $e->errors(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        });
    }
}
