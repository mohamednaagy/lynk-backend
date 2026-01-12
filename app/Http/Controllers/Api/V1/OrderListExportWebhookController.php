<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Notifications\ExportReadyNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class OrderListExportWebhookController extends Controller
{
    /**
     * Handle the webhook request from the NestJS microservice
     * Called when an order list export is completed
     */
    public function __invoke(Request $request): JsonResponse
    {
        // Validate webhook signature for security
        if (! $this->isValidSignature($request)) {
            Log::warning('Unauthorized order list export webhook request', [
                'ip' => $request->ip(),
                'headers' => $request->header(),
            ]);

            return response()->json([
                'error' => 'Unauthorized webhook request',
            ], 401);
        }

        // Validate the incoming webhook request
        try {
            $validatedData = $request->validate([
                'export_type' => 'required|string|max:255',
                'download_url' => 'required|url|max:2048',
                'file_name' => 'required|string|max:500',
                'user_id' => 'required|exists:users,id',
                'message' => 'nullable|string|max:1000',
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
            /* @var User $user */
            $user = User::findOrFail($validatedData['user_id'])->first();

            // Send the export ready notification to the user
            $user->notify(new ExportReadyNotification(
                $validatedData['exportType'],
                $validatedData['download_url'],
                $validatedData['file_name']
            ));

            Log::info('Order list export webhook processed successfully', [
                'user_id' => $validatedData['user_id'],
                'export_type' => $validatedData['export_type'],
                'file_name' => $validatedData['file_name'],
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

    /**
     * Validate the webhook signature to ensure the request is from a trusted source
     */
    public function isValidSignature(Request $request): bool
    {
        // 1. Retrieve the headers and raw payload
        // Note: $request->input() or json_decode() might alter the JSON string (e.g., whitespace).
        // We need the RAW body exactly as it was sent.
        $payload = $request->getContent();

        $signatureHeader = $request->header('X-Signature');
        $timestampHeader = $request->header('X-Timestamp');

        $webhookSecret = config('services.order_export_webhook.secret');

        // 2. Validate headers exist
        if (empty($signatureHeader) || empty($timestampHeader)) {
            return false;
        }

        // 3. Recreate the signature data string
        // Logic matches NestJS: timestamp + '.' + payloadString
        $signatureData = $timestampHeader.'.'.$payload;

        // 4. Generate the expected HMAC hash
        // 'sha256' matches the NestJS algorithm
        // $webhookSecret is your 'notificationToken'
        $expectedSignature = hash_hmac('sha256', $signatureData, $webhookSecret);

        // 5. Compare securely
        // hash_equals prevents timing attacks
        return hash_equals($expectedSignature, $signatureHeader);
    }
}
