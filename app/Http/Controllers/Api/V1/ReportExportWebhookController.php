<?php

namespace App\Http\Controllers\Api\V1;

use App\Events\RealtimeNotification;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Notifications\ExportReadyNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class ReportExportWebhookController extends Controller
{
    /**
     * Handle the webhook request from the NestJS microservice
     * Called when an order list export is completed
     */
    public function __invoke(Request $request): JsonResponse
    {
        try {
            // Validate the new NestJS format
            $validatedData = $request->validate([
                'media_id' => 'required|exists:media,id',
                'export_type' => 'required|string|max:255',
                'model_id' => 'required|exists:users,id', // Using model_id instead of userId
            ]);
        } catch (ValidationException $e) {
            Log::warning('Invalid order list export webhook payload', [
                'errors' => $e->errors(),
                'payload' => $request->all(),
            ]);

            return response()->json([
                'error' => 'Invalid payload',
                'details' => $e->errors(),
            ], 400);
        }

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
