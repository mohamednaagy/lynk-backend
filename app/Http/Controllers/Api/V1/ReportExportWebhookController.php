<?php

namespace App\Http\Controllers\Api\V1;

use App\Events\RealtimeNotification;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\ReportExportWebhookRequest;
use App\Jobs\Reports\Enums\ReportType;
use App\Models\User;
use App\Notifications\ExportReadyNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

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
            $notificationMessage = $this->getNotificationMessage($validatedData['export_type'], $user->preferredLocale());
            event(new RealtimeNotification($notificationMessage, $user->id));

            Log::info('Export webhook processed successfully', [
                'user_id' => $validatedData['model_id'],
                'export_type' => $validatedData['export_type'],
                'media_id' => $validatedData['media_id'],
            ]);

            return $this->successResponse([
                'message' => 'Export webhook processed successfully',
                'processed_at' => now()->toISOString(),
            ]);
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
     * Get the appropriate notification message based on export type and locale
     */
    private function getNotificationMessage(string $exportType, string $locale): string
    {
        return match (Str::convertCase($exportType, MB_CASE_LOWER)) {
            ReportType::OrderList => __('notification.orders-exported', [], $locale),
            ReportType::SupplierMonthlyUsage => __('notification.supplier-monthly-usage-exported', [], $locale),
            default => __('notification.export-completed', [], $locale),
        };
    }
}
