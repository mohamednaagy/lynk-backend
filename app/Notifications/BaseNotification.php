<?php

namespace App\Notifications;

use App\Enums\NotificationChannel;
use App\Enums\SystemNotificationType;
use App\Services\NotificationPreferenceService;
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
        $typeSettings = app(NotificationPreferenceService::class)->getUserNotificationTypeSettings($notifiable, $this->getType());

        return $this->getUserEnabledChannels($typeSettings);
    }

    private function getUserEnabledChannels($typeSettings): array
    {
        $channels = [];
        foreach (NotificationChannel::cases() as $channel) {
            $setting = $typeSettings->where('channel', $channel)->first();
            if ($setting?->is_enabled) {
                $channels[] = $channel->value;
            }
        }

        return $channels;
    }

    /**
     * Get the array representation of the notification.
     * This is used by other channels as well.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            'type' => $this->getType()->value,
            'title' => $this->getTitle($notifiable),
            'description' => $this->getDescription($notifiable),
        ];
    }

    /**
     * Get the title for the notification.
     * Subclasses should override this method to provide a specific title.
     *
     * @param  mixed  $notifiable
     */
    abstract public function getTitle($notifiable): string;

    /**
     * Get the description for the notification.
     * Subclasses should override this method to provide a specific description.
     *
     * @param  mixed  $notifiable
     */
    abstract public function getDescription($notifiable): string;

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
