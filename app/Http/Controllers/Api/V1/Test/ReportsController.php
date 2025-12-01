<?php

namespace App\Http\Controllers\Api\V1\Test;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

class ReportsController extends Controller
{
    /**
     * Test endpoint to run the supplier monthly usage reports command.
     */
    public function generateSupplierMonthlyUsage(): JsonResponse
    {
        try {
            $exitCode = Artisan::call('reports:generate-supplier-monthly-usage');

            if ($exitCode === 0) {
                return response()->json(['success' => true], 200);
            }

            return response()->json(['success' => false], 422);

        } catch (\Exception $e) {
            Log::error('API: Failed to execute GenerateSupplierMonthlyUsageReportsCommand', [
                'error' => $e->getMessage(),
            ]);

            return response()->json(['success' => false], 500);
        }
    }
}
