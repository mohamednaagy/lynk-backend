<?php

namespace App\Http\Controllers\Api\V1\Test;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

class ReportsController extends Controller
{
    /**
     * Test endpoint to run the supplier monthly usage reports command.
     */
    public function generateSupplierMonthlyUsage(Request $request): JsonResponse
    {
        try {
            $params = [];

            $startDate = $request->input('start_date');
            $endDate = $request->input('end_date');

            if ($startDate !== null) {
                $params['--start-date'] = $startDate;
            }

            if ($endDate !== null) {
                $params['--end-date'] = $endDate;
            }

            $exitCode = Artisan::call('reports:generate-supplier-monthly-usage', $params);

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
