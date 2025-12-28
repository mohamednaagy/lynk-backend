<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LoginNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public string $ipAddress;

    public string $timeLogin;

    public string $device;

    public string $platform;

    public string $browser;

    private const NOTIFICATION_TYPE = 'NEW_SIGN_IN';

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
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['mail', 'database'];
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
     * @return array
     */
    public function toArray()
    {
        return [
            'ip_address' => $this->ipAddress,
            'login_timestamp' => $this->timeLogin,
            'device' => $this->device,
            'platform' => $this->platform,
            'browser' => $this->browser,
        ];
    }

    public function databaseType()
    {
        return self::NOTIFICATION_TYPE;
    }

    public function viaQueues()
    {
        return [
            'mail' => 'notifications',
        ];
    }
}
