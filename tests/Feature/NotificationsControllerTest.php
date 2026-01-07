<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\Notification;
use Tests\TestCase;

class NotificationsControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_get_their_notifications_from_last_30_days(): void
    {
        $user = User::factory()->create();

        // Create a notification that is within the last 30 days
        $user->notify(new class extends Notification
        {
            public function via($notifiable)
            {
                return ['database'];
            }

            public function toDatabase($notifiable)
            {
                return [
                    'message' => 'Test notification within 30 days',
                    'action' => 'test_action',
                ];
            }
        });

        // Create another notification that is older than 30 days (should not appear)
        $user->notify(new class extends Notification
        {
            public function via($notifiable)
            {
                return ['database'];
            }

            public function toDatabase($notifiable)
            {
                return [
                    'message' => 'Old notification',
                    'action' => 'old_action',
                ];
            }

            public function shouldQueue(): bool
            {
                return false;
            }
        });

        // Update the created_at timestamp to be older than 30 days
        $oldNotification = $user->notifications()->where('data->message', 'Old notification')->first();
        if ($oldNotification) {
            $oldNotification->update(['created_at' => now()->subDays(31)]);
        }

        $response = $this->actingAs($user, 'api')
            ->getJson('/api/v1/notifications');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'type',
                        'data',
                        'read_at',
                        'created_at',
                        'updated_at',
                    ],
                ],
                'meta' => [
                    'pagination' => [
                        'total',
                        'count',
                        'per_page',
                        'current_page',
                        'total_pages',
                        'links',
                    ],
                ],
            ]);

        // Should only contain the recent notification, not the old one
        $response->assertJsonCount(1, 'data');
        $response->assertJson([
            'data' => [
                [
                    'data' => [
                        'message' => 'Test notification within 30 days',
                        'action' => 'test_action',
                    ],
                ],
            ],
        ]);
    }

    public function test_notifications_are_returned_in_descending_order_by_created_at(): void
    {
        $user = User::factory()->create();

        // Create two notifications with different timestamps using the notification system
        $user->notify(new class extends Notification
        {
            public function via($notifiable)
            {
                return ['database'];
            }

            public function toDatabase($notifiable)
            {
                return ['message' => 'Earlier notification'];
            }
        });

        // Update the created_at to be earlier
        $earlierNotification = $user->notifications()->where('data->message', 'Earlier notification')->first();
        if ($earlierNotification) {
            $earlierNotification->update(['created_at' => now()->subSecond()]);
        }

        $user->notify(new class extends Notification
        {
            public function via($notifiable)
            {
                return ['database'];
            }

            public function toDatabase($notifiable)
            {
                return ['message' => 'Latest notification'];
            }
        });

        // Update the created_at to be later
        $latestNotification = $user->notifications()->where('data->message', 'Latest notification')->first();
        if ($latestNotification) {
            $latestNotification->update(['created_at' => now()]);
        }

        $response = $this->actingAs($user, 'api')
            ->getJson('/api/v1/notifications');

        $response->assertStatus(200);

        $data = $response->json('data');

        // First item should be the latest notification (most recent first)
        $this->assertEquals('Latest notification', $data[0]['data']['message']);
        // Second item should be the earlier notification
        $this->assertEquals('Earlier notification', $data[1]['data']['message']);
    }

    public function test_notifications_are_paginated_by_10(): void
    {
        $user = User::factory()->create();

        // Create 15 notifications to test pagination
        for ($i = 0; $i < 15; $i++) {
            $user->notify(new class($i) extends Notification
            {
                private $index;

                public function __construct($index)
                {
                    $this->index = $index;
                }

                public function via($notifiable)
                {
                    return ['database'];
                }

                public function toDatabase($notifiable)
                {
                    return ['message' => "Notification #{$this->index}"];
                }
            });
        }

        $response = $this->actingAs($user, 'api')
            ->getJson('/api/v1/notifications');

        $response->assertStatus(200)
            ->assertJson([
                'meta' => [
                    'pagination' => [
                        'per_page' => 10,
                        'total' => 15,
                        'count' => 10, // Should show 10 per page
                    ],
                ],
            ]);

        // Should return 10 items on the first page
        $response->assertJsonCount(10, 'data');
    }

    public function test_unauthenticated_user_cannot_access_notifications(): void
    {
        $response = $this->getJson('/api/v1/notifications');

        $response->assertStatus(401);
    }
}
