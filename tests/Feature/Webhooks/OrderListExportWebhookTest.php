<?php

namespace Tests\Feature\Webhooks;

use App\Models\User;
use App\Notifications\ExportReadyNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class OrderListExportWebhookTest extends TestCase
{
    use RefreshDatabase;

    public User $user;

    protected string $webhookSecret;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->webhookSecret = 'test_webhook_secret';

        // Set the webhook secret for testing
        config(['services.order_export_webhook.secret' => $this->webhookSecret]);

    }

    /** @test */
    public function it_processes_valid_order_list_export_webhook_request(): void
    {
        Notification::fake();
        Queue::fake(); // Ensure notifications are processed synchronously

        // Create a media record for testing
        $media = \App\Models\Media::create([
            'model_type' => User::class,
            'model_id' => $this->user->id,
            'collection_name' => 'exports',
            'name' => 'test_export.xlsx',
            'file_name' => 'test_export.xlsx',
            'mime_type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'disk' => 'public',
            'size' => 1024,
            'manipulations' => [],
            'custom_properties' => [],
            'generated_conversions' => [],
            'responsive_images' => [],
            'order_column' => 1,
        ]);

        $payload = [
            'mediaId' => $media->id,
            'exportType' => 'Order List',
            'downloadUrl' => 'https://example.com/downloads/orders.xlsx',
            'fileName' => 'orders_export_2023_01_01.xlsx',
            'modelId' => $this->user->id,
            'message' => 'Your order list export is ready for download.',
        ];

        $timestamp = time();
        $signature = hash_hmac('sha256', $timestamp.'.'.json_encode($payload), $this->webhookSecret);

        // Verify the media record exists before sending the request
        $this->assertNotNull(\App\Models\Media::find($media->id));

        $response = $this->postJson('/api/v1/order-list-export/webhook', $payload, [
            'X-Signature' => $signature,
            'X-Timestamp' => $timestamp,
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'message' => 'Webhook processed successfully',
        ]);

        Notification::assertSentTo($this->user, ExportReadyNotification::class, function ($notification) use ($payload) {
            return $notification->exportType === $payload['exportType'] &&
                   $notification->fileName === $payload['fileName'];
        });
    }

    /** @test */
    public function it_rejects_requests_when_webhook_secret_is_empty(): void
    {
        // Temporarily set the webhook secret to empty
        config(['services.order_export_webhook.secret' => '']);

        // Create a media record for testing
        $media = \App\Models\Media::create([
            'model_type' => User::class,
            'model_id' => $this->user->id,
            'collection_name' => 'exports',
            'name' => 'test_export.xlsx',
            'file_name' => 'test_export.xlsx',
            'mime_type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'disk' => 'public',
            'size' => 1024,
            'manipulations' => [],
            'custom_properties' => [],
            'generated_conversions' => [],
            'responsive_images' => [],
            'order_column' => 1,
        ]);

        $payload = [
            'mediaId' => $media->id,
            'exportType' => 'Order List',
            'downloadUrl' => 'https://example.com/downloads/orders.xlsx',
            'fileName' => 'orders_export_2023_01_01.xlsx',
            'modelId' => $this->user->id,
            'message' => 'Your order list export is ready for download.',
        ];

        $timestamp = time();
        $signature = hash_hmac('sha256', $timestamp.'.'.json_encode($payload), ''); // Empty secret

        $response = $this->postJson('/api/v1/order-list-export/webhook', $payload, [
            'X-Signature' => $signature,
            'X-Timestamp' => $timestamp,
        ]);

        $response->assertStatus(401);
        $response->assertJson([
            'error' => 'Unauthorized webhook request',
        ]);
    }

    /** @test */
    public function it_rejects_unauthorized_webhook_requests(): void
    {
        // Create a media record for testing
        $media = \App\Models\Media::create([
            'model_type' => User::class,
            'model_id' => $this->user->id,
            'collection_name' => 'exports',
            'name' => 'test_export.xlsx',
            'file_name' => 'test_export.xlsx',
            'mime_type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'disk' => 'public',
            'size' => 1024,
            'manipulations' => [],
            'custom_properties' => [],
            'generated_conversions' => [],
            'responsive_images' => [],
            'order_column' => 1,
        ]);

        $payload = [
            'mediaId' => $media->id,
            'exportType' => 'Order List',
            'downloadUrl' => 'https://example.com/downloads/orders.xlsx',
            'fileName' => 'orders_export_2023_01_01.xlsx',
            'modelId' => $this->user->id,
        ];

        $response = $this->postJson('/api/v1/order-list-export/webhook', $payload, [
            'X-Signature' => 'invalid_signature',
            'X-Timestamp' => time(),
        ]);

        $response->assertStatus(401);
        $response->assertJson([
            'error' => 'Unauthorized webhook request',
        ]);
    }

    /** @test */
    public function it_rejects_requests_with_expired_timestamp(): void
    {
        Notification::fake();

        // Create a media record for testing
        $media = \App\Models\Media::create([
            'model_type' => User::class,
            'model_id' => $this->user->id,
            'collection_name' => 'exports',
            'name' => 'test_export.xlsx',
            'file_name' => 'test_export.xlsx',
            'mime_type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'disk' => 'public',
            'size' => 1024,
            'manipulations' => [],
            'custom_properties' => [],
            'generated_conversions' => [],
            'responsive_images' => [],
            'order_column' => 1,
        ]);

        $payload = [
            'mediaId' => $media->id,
            'exportType' => 'Order List',
            'downloadUrl' => 'https://example.com/downloads/orders.xlsx',
            'fileName' => 'orders_export_2023_01_01.xlsx',
            'modelId' => $this->user->id,
        ];

        // Use a timestamp that's more than 5 minutes ago
        $expiredTimestamp = time() - 400; // 400 seconds = 6+ minutes ago
        $signature = hash_hmac('sha256', $expiredTimestamp.'.'.json_encode($payload), $this->webhookSecret);

        $response = $this->postJson('/api/v1/order-list-export/webhook', $payload, [
            'X-Signature' => $signature,
            'X-Timestamp' => $expiredTimestamp,
        ]);

        $response->assertStatus(401);
        $response->assertJson([
            'error' => 'Unauthorized webhook request',
        ]);

        Notification::assertNothingSent();
    }

    /** @test */
    public function it_returns_error_for_invalid_payload(): void
    {
        Notification::fake();

        // Create a media record for testing
        $media = \App\Models\Media::create([
            'model_type' => User::class,
            'model_id' => $this->user->id,
            'collection_name' => 'exports',
            'name' => 'test_export.xlsx',
            'file_name' => 'test_export.xlsx',
            'mime_type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'disk' => 'public',
            'size' => 1024,
            'manipulations' => [],
            'custom_properties' => [],
            'generated_conversions' => [],
            'responsive_images' => [],
            'order_column' => 1,
        ]);

        $payload = [
            'mediaId' => $media->id,
            'exportType' => '', // Invalid - empty
            'downloadUrl' => 'invalid-url', // Invalid URL
            'fileName' => str_repeat('a', 600), // Too long
            'modelId' => 999999, // Non-existent user
        ];

        $timestamp = time();
        $signature = hash_hmac('sha256', $timestamp.'.'.json_encode($payload), $this->webhookSecret);

        $response = $this->postJson('/api/v1/order-list-export/webhook', $payload, [
            'X-Signature' => $signature,
            'X-Timestamp' => $timestamp,
        ]);

        $response->assertStatus(400);
        $response->assertJsonStructure([
            'error',
            'details',
        ]);

        Notification::assertNothingSent();
    }
}
