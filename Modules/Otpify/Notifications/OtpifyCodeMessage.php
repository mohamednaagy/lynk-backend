<?php

namespace Modules\Otpify\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OtpifyCodeMessage extends Notification
{
    use Queueable;

    protected $otpCode;
    protected $expiredAt;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($otpCode, $expiredAt)
    {
        $this->otpCode      = $otpCode;
        $this->expiredAt    = $expiredAt;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['mail'];
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
                    ->line('Your OTP Code is: '. $this->otpCode .'.')
                    ->action('Verify Here', url('/'))
                    ->line('The code will expire in '. $this->expiredAt->diffInMinutes(now()) .' Minutes')
                    ->line('If you have not tried to login, ignore this message.');
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
            //
        ];
    }
}
