<?php

namespace App\Http\Controllers;

use App\Services\Health\HealthCheckService;
use Illuminate\Http\JsonResponse;

class HealthCheckController extends Controller
{
    /**
     * Detailed health check endpoint
     */
    public function __invoke(HealthCheckService $healthCheck): JsonResponse
    {
        $result = $healthCheck->run();

        return response()->json(
            $result,
            $result['status'] === 'healthy' ? 200 : 503
        );
    }
}
