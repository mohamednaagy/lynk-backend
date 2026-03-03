<?php

namespace App\Notifications;

use App\Enums\NotificationChannel;
use App\Enums\SystemNotificationType;
use App\Events\RealtimeNotification;
use App\Services\NotificationPreferenceService;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

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
        // Check if the user has allowed roles for this notification type
        if (! $this->userHasAllowedRole($notifiable)) {
            return [];
        }

        $typeSettings = app(NotificationPreferenceService::class)->getUserNotificationTypeSettings($notifiable, $this->getType());

        return $this->getUserEnabledChannels($typeSettings);
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
     * Send this notification as a single email using the content from toMail().
     *
     * - Admins (from getBccUsers()) are always added as BCC.
     * - If $recipients is not empty, they are set as To; otherwise the email has no To recipients.
     *
     * @param  array<int, string>  $recipients  Primary recipient email addresses (To). May be empty for admin-only sends.
     */
    public function sendTo(array $recipients): void
    {
        $admins = $this->getBccUsers()->toArray();
        $mailMessage = $this->toMail(new AnonymousNotifiable); // @phpstan-ignore method.notFound

        Mail::html((string) $mailMessage->render(), function ($message) use ($mailMessage, $recipients, $admins) {
            if ($mailMessage->subject) {
                $message->subject($mailMessage->subject);
            }

            $message->bcc($admins);

            if (! empty($recipients)) {
                $message->to($recipients);
            }
        });
    }

    /**
     * Store a portal (database) notification and push realtime event for each user.
     * Bypasses via() to avoid triggering mail channel (double-email prevention).
     * Mirrors what Laravel's DatabaseChannel::send() does internally.
     *
     * @param  Collection<int, \App\Models\User>  $users
     */
    public function sendPortalTo(Collection $users): void
    {
        $users->each(function ($user) {
            $user->notifications()->create([
                'id' => (string) Str::uuid(),
                'type' => get_class($this),
                'data' => $this->toArray($user),
                'read_at' => null,
            ]);
            event(new RealtimeNotification($this->getTitle($user), $user->id));
        });
    }

    /**
     * Fetches portal-enabled users for this notification's type then calls sendPortalTo().
     */
    public function sendPortal(): void
    {
        $this->sendPortalTo(
            app(NotificationPreferenceService::class)->getEnabledUsersForPortal($this->getType())
        );
    }

    protected function getBccUsers(): Collection
    {
        $notificationPreferenceService = app(NotificationPreferenceService::class);

        return $notificationPreferenceService->getEligibleAdminsOrManagers($this->getType())->pluck('email');
    }

    protected function getActionURL(): string
    {
        return '';
    }

    /**
     * Check if the user has allowed roles for this notification type.
     *
     * @param  mixed  $notifiable
     */
    private function userHasAllowedRole($notifiable): bool
    {
        $notificationType = $this->getType()->value;
        $allowedRoles = config("notification-types.{$notificationType}.roles", []);

        // If there are no allowed roles defined in config, allow all users
        if (empty($allowedRoles)) {
            return true;
        }

        // Validate that the user has one of the allowed roles
        foreach ($allowedRoles as $role) {
            if ($notifiable->hasRole($role)) {
                return true;
            }
        }

        // User doesn't have any of the allowed roles
        return false;
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
}
