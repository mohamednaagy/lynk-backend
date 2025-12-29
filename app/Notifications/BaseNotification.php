<?php

namespace App\Notifications;

use App\Enums\NotificationChannel;
use App\Enums\SystemNotificationType;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * BaseNotification
 *
 * `implements ShouldQueue` dictates whether a notification is actually queued. We leave the implements
 * ShouldQueue decision to concrete notification classes for flexibility, as not all notifications require queuing.
 */
abstract class BaseNotification extends Notification
{
    use Queueable;

    /**
     * Get the notification's type.
     * This should be a value from SystemNotificationType enum.
     */
    abstract public function getType(): SystemNotificationType;

    /**
     * Get the notification's delivery channels based on user's notification settings.
     *
     * @param  mixed  $notifiable
     */
    public function via($notifiable): array
    {
        $typeSettings = $this->getUserNotificationTypeSettings($notifiable);

        return $this->getUserEnabledChannels($typeSettings);
    }

    private function getUserNotificationTypeSettings($notifiable): mixed
    {
        // Get the user's notification settings for this type
        // Use the already loaded relationship if available to avoid N+1 queries
        return $notifiable->relationLoaded('notificationSettings')
                    ? $notifiable->notificationSettings->where('notification_type', $this->getType()->value)
                    : $notifiable->notificationSettings()
                        ->where('notification_type', $this->getType()->value)
                        ->get();
    }

    private function getUserEnabledChannels($typeSettings): array
    {
        $channels = [];
        foreach (NotificationChannel::cases() as $channel) {
            $setting = $typeSettings->where('channel', $channel->value)->first();
            if ($setting?->is_enabled) {
                $channels[] = $channel->value;
            }
        }

        return $channels;
    }

    /**
     * Get the array representation of the notification.
     * This is critical for database notifications.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            'type' => $this->getType()->value,
        ];
    }

    /**
     * Specify which queue should handle which channels.
     *
     * @return array
     */
    public function viaQueues()
    {
        return [
            'mail' => 'notifications',
        ];
    }
}
