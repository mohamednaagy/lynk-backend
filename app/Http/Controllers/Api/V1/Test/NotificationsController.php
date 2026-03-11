<?php

namespace App\Http\Controllers\Api\V1\Test;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

class NotificationsController extends Controller
{
    public function sendWalletBalanceLimitNotification(): JsonResponse
    {
        return $this->processCommand('notifications:send-wallet-remaining-balance');
    }

    public function sendInProgressOrders(): JsonResponse
    {
        return $this->processCommand('notifications:send-in-progress-orders');
    }

    private function processCommand(string $command, array $parameters = []): JsonResponse
    {
        try {
            $exitCode = Artisan::call($command, $parameters);

            if ($exitCode === 0) {
                return response()->json(['success' => true]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Command failed.',
            ], 500);
        } catch (\Exception $e) {
            Log::error("API: Failed to execute $command", [
                'error' => $e->getMessage(),
            ]);

            return response()->json(['success' => false], 500);
        }
    }
}
