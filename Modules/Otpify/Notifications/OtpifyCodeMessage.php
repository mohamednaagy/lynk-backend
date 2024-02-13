<?php

namespace Modules\Otpify\Notifications;

use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OtpifyCodeMessage extends Notification
{
    use Queueable;

    protected string $otpCode;

    protected Carbon $expirationDate;

    /**
     * Create a new notification instance.
     */
    public function __construct(string $otpCode, Carbon $expirationDate)
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
            ->subject(trans('otpify::email.subject'))
            ->theme('default')
            ->markdown('otpify::email.otp-email', [
                'notifiable' => $notifiable,
                'otpCode' => $this->otpCode,
                'expirationDate' => $this->expirationDate,
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
            //
        ];
    }
}
