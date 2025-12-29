<?php

namespace App\Notifications;

use App\Enums\SystemNotificationType;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class LoginNotification extends BaseNotification implements ShouldQueue
{
    public string $ipAddress;

    public string $timeLogin;

    public string $device;

    public string $platform;

    public string $browser;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(string $ipAddress, string $timeLogin, string $device, string $platform, string $browser)
    {
        $this->ipAddress = $ipAddress;
        $this->timeLogin = $timeLogin;
        $this->device = $device;
        $this->platform = $platform;
        $this->browser = $browser;
    }

    /**
     * Get the notification's type.
     * This should be a value from SystemNotificationType enum.
     */
    public function getType(): SystemNotificationType
    {
        return SystemNotificationType::NEW_SIGN_IN;
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject(__('emails/login-notification.subject'))
            ->markdown('emails.login-notification', [
                'timeLogin' => $this->timeLogin,
                'ipAddress' => $this->ipAddress,
                'device' => $this->device,
                'platform' => $this->platform,
                'browser' => $this->browser,
            ]);
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            ...parent::toArray($notifiable),
            'ip_address' => $this->ipAddress,
            'login_timestamp' => $this->timeLogin,
            'device' => $this->device,
            'platform' => $this->platform,
            'browser' => $this->browser,
        ];
    }
}
