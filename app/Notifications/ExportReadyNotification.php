<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ExportReadyNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public string $fileUrl) {}

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('Your Export File is Ready')
            ->greeting('Hello '.$notifiable->name.',')
            ->line('Your export file has been successfully generated.')
            ->action('Download File', $this->fileUrl)
            ->line('This link may expire soon. Please download it as soon as possible.');
    }
}
