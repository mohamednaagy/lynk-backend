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
     * Called when an export is completed
     */
    public function __invoke(ReportExportWebhookRequest $request): JsonResponse
    {
        $validatedData = $request->validated();

        try {
            // Find the user who initiated the export
            $user = User::findOrFail($validatedData['model_id']);

            Log::info('User found successfully', ['user_id' => $user->id]);

            // Send the export ready notification to the user
            // This might throw an exception if media doesn't exist
            $user->notify(new ExportReadyNotification(
                $validatedData['export_type'],
                $validatedData['media_id'],
            ));

            Log::info('Notification created successfully');

            // Send realtime notification with export-type-specific message
            $notificationMessage = $this->getNotificationMessage($validatedData['export_type']);
            event(new RealtimeNotification(__($notificationMessage), $user->id));

            Log::info('Export webhook processed successfully', [
                'user_id' => $validatedData['model_id'],
                'export_type' => $validatedData['export_type'],
                'media_id' => $validatedData['media_id'],
            ]);

            return response()->json([
                'message' => 'Webhook processed successfully',
                'processed_at' => now()->toISOString(),
            ], 200);
        } catch (\Exception $e) {
            Log::error('Failed to process export webhook', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'payload' => $validatedData,
            ]);

            return $this->errorResponse('Failed to process webhook');
        }
    }

    /**
     * Get the appropriate notification message based on export type
     */
    private function getNotificationMessage(string $exportType): string
    {
        return match ($exportType) {
            'ORDER_LIST', 'order_list' => 'notification.orders-exported',
            'SUPPLIER_MONTHLY_USAGE', 'supplier_monthly_usage' => 'notification.supplier-monthly-usage-exported',
            default => 'notification.export-completed'
        };
    }
}
