<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\SystemNotificationType;
use App\Models\User;
use App\Notifications\BaseNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationsControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_get_their_notifications_from_last_30_days(): void
    {
        $user = User::factory()->create();

        // Create a notification that is within the last 30 days
        $user->notify(new class extends BaseNotification
        {
            public function getType(): SystemNotificationType
            {
                return SystemNotificationType::ORDER_CANCELLED;
            }

            public function via($notifiable): array
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
        $user->notify(new class extends BaseNotification
        {
            public function getType(): SystemNotificationType
            {
                return SystemNotificationType::ORDER_CANCELLED;
            }

            public function via($notifiable): array
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
        $user->notify(new class extends BaseNotification
        {
            public function getType(): SystemNotificationType
            {
                return SystemNotificationType::ORDER_CANCELLED;
            }

            public function via($notifiable): array
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

        $user->notify(new class extends BaseNotification
        {
            public function getType(): SystemNotificationType
            {
                return SystemNotificationType::ORDER_CANCELLED;
            }

            public function via($notifiable): array
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
            $user->notify(new class($i) extends BaseNotification
            {
                private $index;

                public function __construct($index)
                {
                    $this->index = $index;
                }

                public function getType(): SystemNotificationType
                {
                    return SystemNotificationType::ORDER_CANCELLED;
                }

                public function via($notifiable): array
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

    public function test_user_can_get_unread_notifications_only(): void
    {
        $user = User::factory()->create();

        // Create an unread notification
        $user->notify(new class extends BaseNotification
        {
            public function getType(): SystemNotificationType
            {
                return SystemNotificationType::ORDER_CANCELLED;
            }

            public function via($notifiable): array
            {
                return ['database'];
            }

            public function toDatabase($notifiable)
            {
                return [
                    'message' => 'Unread notification',
                    'action' => 'unread_action',
                ];
            }
        });

        // Create a read notification
        $user->notify(new class extends BaseNotification
        {
            public function getType(): SystemNotificationType
            {
                return SystemNotificationType::ORDER_CANCELLED;
            }

            public function via($notifiable): array
            {
                return ['database'];
            }

            public function toDatabase($notifiable)
            {
                return [
                    'message' => 'Read notification',
                    'action' => 'read_action',
                ];
            }
        });

        // Mark the second notification as read
        $readNotification = $user->notifications()->where('data->message', 'Read notification')->first();
        if ($readNotification) {
            $readNotification->markAsRead();
        }

        // Test with unread_only parameter set to true
        $response = $this->actingAs($user, 'api')
            ->getJson('/api/v1/notifications?unread_only=true');

        $response->assertStatus(200);

        // Should only return the unread notification
        $response->assertJsonCount(1, 'data');
        $response->assertJson([
            'data' => [
                [
                    'data' => [
                        'message' => 'Unread notification',
                        'action' => 'unread_action',
                    ],
                    'read_at' => null,
                ],
            ],
        ]);

        // Test with unread_only parameter set to false (should return all notifications)
        $response = $this->actingAs($user, 'api')
            ->getJson('/api/v1/notifications?unread_only=false');

        $response->assertStatus(200);

        // Should return both notifications
        $response->assertJsonCount(2, 'data');
    }

    public function test_user_can_mark_a_notification_as_read(): void
    {
        $user = User::factory()->create();

        // Create an unread notification
        $user->notify(new class extends BaseNotification
        {
            public function getType(): SystemNotificationType
            {
                return SystemNotificationType::ORDER_CANCELLED;
            }

            public function via($notifiable): array
            {
                return ['database'];
            }

            public function toDatabase($notifiable)
            {
                return [
                    'message' => 'Test notification to mark as read',
                    'action' => 'test_action',
                ];
            }
        });

        $notification = $user->notifications()->first();
        $this->assertNull($notification->read_at);

        // Mark the notification as read
        $response = $this->actingAs($user, 'api')
            ->putJson("/api/v1/notifications/{$notification->id}/read");

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Notification marked as read',
            ]);

        // Verify the notification is now marked as read
        $notification->refresh();
        $this->assertNotNull($notification->read_at);
    }

    public function test_user_cannot_mark_nonexistent_notification_as_read(): void
    {
        $user = User::factory()->create();

        // Try to mark a non-existent notification as read
        $response = $this->actingAs($user, 'api')
            ->putJson('/api/v1/notifications/999/read');

        $response->assertStatus(404)
            ->assertJson([
                'message' => 'This item was not found.',
                'code' => 1052,
            ]);
    }

    public function test_user_cannot_mark_another_users_notification_as_read(): void
    {
        $user = User::factory()->create();
        $anotherUser = User::factory()->create();

        // Create a notification for another user
        $anotherUser->notify(new class extends BaseNotification
        {
            public function getType(): SystemNotificationType
            {
                return SystemNotificationType::ORDER_CANCELLED;
            }

            public function via($notifiable): array
            {
                return ['database'];
            }

            public function toDatabase($notifiable)
            {
                return [
                    'message' => 'Another user notification',
                ];
            }
        });

        $notification = $anotherUser->notifications()->first();

        // Try to mark another user's notification as read
        $response = $this->actingAs($user, 'api')
            ->putJson("/api/v1/notifications/{$notification->id}/read");

        $response->assertStatus(404)
            ->assertJson([
                'message' => 'This item was not found.',
                'code' => 1052,
            ]);
    }

    public function test_user_can_mark_all_notifications_as_read(): void
    {
        $user = User::factory()->create();

        // Create multiple unread notifications
        for ($i = 0; $i < 3; $i++) {
            $user->notify(new class($i) extends BaseNotification
            {
                private $index;

                public function __construct($index)
                {
                    $this->index = $index;
                }

                public function getType(): SystemNotificationType
                {
                    return SystemNotificationType::ORDER_CANCELLED;
                }

                public function via($notifiable): array
                {
                    return ['database'];
                }

                public function toDatabase($notifiable)
                {
                    return [
                        'message' => "Unread notification #{$this->index}",
                    ];
                }
            });
        }

        // Verify all notifications are unread
        $unreadCount = $user->unreadNotifications()->count();
        $this->assertEquals(3, $unreadCount);

        // Mark all notifications as read
        $response = $this->actingAs($user, 'api')
            ->putJson('/api/v1/notifications/read-all');

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'All notifications marked as read',
            ]);

        // Verify all notifications are now read
        $unreadCount = $user->unreadNotifications()->count();
        $this->assertEquals(0, $unreadCount);
    }

    public function test_unauthenticated_user_cannot_mark_notification_as_read(): void
    {
        $response = $this->putJson('/api/v1/notifications/1/read');

        $response->assertStatus(401);
    }

    public function test_unauthenticated_user_cannot_mark_all_notifications_as_read(): void
    {
        $response = $this->putJson('/api/v1/notifications/read-all');

        $response->assertStatus(401);
    }
}
