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

        try {
            // Validate the new NestJS format
            $validatedData = $request->validate([
                'mediaId' => 'required|exists:media,id',
                'exportType' => 'required|string|max:255',
                'downloadUrl' => 'required|string|max:2048', // Changed from url to string to allow relative paths
                'fileName' => 'required|string|max:500',
                'modelId' => 'required|exists:users,id', // Using modelId instead of userId
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
            $user = User::findOrFail($validatedData['modelId']);

            // Send the export ready notification to the user
            $user->notify(new ExportReadyNotification(
                $validatedData['exportType'],
                $validatedData['mediaId'],
                $validatedData['fileName']
            ));

            Log::info('Order list export webhook processed successfully', [
                'user_id' => $validatedData['modelId'],
                'export_type' => $validatedData['exportType'],
                'file_name' => $validatedData['fileName'],
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

        // 3. Check if timestamp is within acceptable range (5 minutes)
        $currentTime = time();

        // Handle both millisecond and second timestamps for validation purposes
        $validationTimestamp = (int) $timestampHeader;

        // If timestamp is too far in the future (indicating milliseconds), convert to seconds for comparison
        // A timestamp from year 3000+ is definitely in milliseconds
        if ($validationTimestamp > 100000000000) { // Year 1973+ in seconds, much less in milliseconds
            $validationTimestamp = intval($validationTimestamp / 1000);
        }

        $timeDifference = abs($currentTime - $validationTimestamp);
        $maxTimeDifference = 5 * 60; // 5 minutes in seconds

        if ($timeDifference > $maxTimeDifference) {
            return false;
        }

        // 4. Recreate the signature data string
        // Use the original timestamp header for signature calculation (to match what sender used)
        $signatureData = $timestampHeader.'.'.$payload;

        // 5. Generate the expected HMAC hash
        // 'sha256' matches the NestJS algorithm
        // $webhookSecret is your 'notificationToken'
        $expectedSignature = hash_hmac('sha256', $signatureData, $webhookSecret);

        // 6. Compare securely
        // hash_equals prevents timing attacks
        return hash_equals($expectedSignature, $signatureHeader);
    }
}
