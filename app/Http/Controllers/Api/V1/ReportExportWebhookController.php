<?php

namespace App\Http\Controllers\Api\V1;

use App\Events\RealtimeNotification;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\ReportExportWebhookRequest;
use App\Models\User;
use App\Notifications\ExportReadyNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class ReportExportWebhookController extends Controller
{
    /**
     * Handle the webhook request from the NestJS microservice
     * Called when an order list export is completed
     */
    public function __invoke(ReportExportWebhookRequest $request): JsonResponse
    {
        $validatedData = $request->validated();

        try {
            // Find the user who initiated the export
            $user = User::findOrFail($validatedData['model_id']);

            // Send the export ready notification to the user
            $user->notify(new ExportReadyNotification(
                $validatedData['export_type'],
                $validatedData['media_id'],
            ));

            event(new RealtimeNotification(__('notification.orders-exported'), $user->id));

            Log::info('Order list export webhook processed successfully', [
                'user_id' => $validatedData['model_id'],
                'export_type' => $validatedData['export_type'],
                'media_id' => $validatedData['media_id'],
            ]);

            return response()->json([
                'message' => 'Webhook processed successfully',
                'processed_at' => now()->toISOString(),
            ], 200);
        } catch (\Exception $e) {
            Log::error('Failed to process order list export webhook', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'payload' => $validatedData,
            ]);

            return response()->json([
                'error' => 'Failed to process webhook',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
