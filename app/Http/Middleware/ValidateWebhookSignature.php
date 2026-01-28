<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class ValidateWebhookSignature
{
    public function handle(Request $request, Closure $next): Response
    {
        // Validate webhook signature for security
        if (! $this->isValidSignature($request)) {
            Log::warning('Unauthorized webhook request', [
                'ip' => $request->ip(),
                'headers' => $request->header(),
            ]);

            return response()->json([
                'error' => 'Unauthorized webhook request',
            ], 401);
        }

        return $next($request);
    }

    /**
     * Validate the webhook signature to ensure the request is from a trusted source
     */
    private function isValidSignature(Request $request): bool
    {
        // 1. Retrieve the headers and raw payload
        // Note: $request->input() or json_decode() might alter the JSON string (e.g., whitespace).
        // We need the RAW body exactly as it was sent.
        $payload = $request->getContent();

        $signatureHeader = $request->header('X-Signature');
        $timestampHeader = $request->header('X-Timestamp');

        $webhookSecret = config('services.order_export_webhook.secret');

        // Explicitly reject requests when the webhook secret is empty/misconfigured
        if (empty($webhookSecret)) {
            return false;
        }

        // 2. Validate headers exist
        if (empty($signatureHeader) || empty($timestampHeader)) {
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
