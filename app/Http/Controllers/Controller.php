<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller as BaseController;
use Symfony\Component\HttpFoundation\Response;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    /**
     * @param array $data
     * @param int $statusCode
     * @return JsonResponse
     */
    protected function successResponse(
        array $data = [],
        int   $statusCode = Response::HTTP_OK
    ): JsonResponse
    {
        return response()->json([
            'data' => $data
        ], $statusCode);
    }

    /**
     * @param string $message
     * @param int $code
     * @param int $statusCode
     * @return JsonResponse
     */
    protected function errorResponse(
        string $message = 'something went wrong',
        int    $code = 400,
        int    $statusCode = Response::HTTP_BAD_REQUEST
    ): JsonResponse
    {
        return response()->json([
            'message' => $message,
            'code' => $code
        ], $statusCode);
    }
}
