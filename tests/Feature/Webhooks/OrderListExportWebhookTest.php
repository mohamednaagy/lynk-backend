<?php

namespace Tests\Feature\Webhooks;

use App\Models\Media;
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
            'media_id' => $media->id,
            'export_type' => 'ORDER_LIST',
            'model_id' => $this->user->id,
        ];

        $timestamp = time();
        $signature = hash_hmac('sha256', $timestamp.'.'.json_encode($payload), $this->webhookSecret);

        // Verify the media record exists before sending the request
        $this->assertNotNull(Media::find($media->id), 'Media record should exist with ID: '.$media->id);

        $response = $this->postJson('/api/v1/report-service/callback', $payload, [
            'X-Signature' => $signature,
            'X-Timestamp' => $timestamp,
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'message' => 'Webhook processed successfully',
        ]);

        Notification::assertSentTo($this->user, ExportReadyNotification::class, function ($notification) use ($payload) {
            return $notification->getExportType() === $payload['export_type'];
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
            'media_id' => $media->id,
            'export_type' => 'ORDER_LIST',
            'model_id' => $this->user->id,
        ];

        $timestamp = time();
        $signature = hash_hmac('sha256', $timestamp.'.'.json_encode($payload), ''); // Empty secret

        $response = $this->postJson('/api/v1/report-service/callback', $payload, [
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
            'media_id' => $media->id,
            'export_type' => 'ORDER_LIST',
            'model_id' => $this->user->id,
        ];

        $response = $this->postJson('/api/v1/report-service/callback', $payload, [
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
            'media_id' => $media->id,
            'export_type' => 'ORDER_LIST',
            'model_id' => $this->user->id,
        ];

        // Use a timestamp that's more than 5 minutes ago
        $expiredTimestamp = time() - 400; // 400 seconds = 6+ minutes ago
        $signature = hash_hmac('sha256', $expiredTimestamp.'.'.json_encode($payload), $this->webhookSecret);

        $response = $this->postJson('/api/v1/report-service/callback', $payload, [
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
            'media_id' => $media->id,
            'export_type' => '', // Invalid - empty
            'model_id' => 999999, // Non-existent user
        ];

        $timestamp = time();
        $signature = hash_hmac('sha256', $timestamp.'.'.json_encode($payload), $this->webhookSecret);

        $response = $this->postJson('/api/v1/report-service/callback', $payload, [
            'X-Signature' => $signature,
            'X-Timestamp' => $timestamp,
        ]);

        $response->assertStatus(422);
        $response->assertJsonStructure([
            'message',
            'errors',
        ]);

        Notification::assertNothingSent();
    }
}
