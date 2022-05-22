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
    protected $expirationDate;

    /**
     * Create a new notification instance.
     *
     * @param $otpCode
     * @param $expirationDate
     */
    public function __construct($otpCode, $expirationDate)
    {
        $this->otpCode = $otpCode;
        $this->expirationDate = $expirationDate;
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
                    ->greeting(trans('otpify::email.greeting', ['name' => $notifiable->fullName]))
                    ->line(trans('otpify::email.otp_code', ['code' => $this->otpCode]))
                    ->action(trans('otpify::email.verify_here'), url('/'))
                    ->line(trans('otpify::email.expire_at', ['time' => $this->expirationDate->diffInMinutes(now())]))
                    ->line(trans('otpify::email.ignore_message'));
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
