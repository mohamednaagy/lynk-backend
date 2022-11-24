<?php

namespace App\Notifications;

use App\Enums\NotificationType;
use DragonCode\Contracts\Queue\ShouldQueue;
use Illuminate\Bus\Queueable;
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

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($ipAddress, $timeLogin, $device, $platform, $browser)
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
            ->metadata('notifiable_type', NotificationType::NewSignIn)
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
        return NotificationType::NewSignIn;
    }
}
