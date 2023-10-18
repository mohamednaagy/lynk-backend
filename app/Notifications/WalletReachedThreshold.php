<?php

namespace App\Notifications;

use App\Enums\WalletNotificationType;
use App\Models\WalletNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Config;
use NumberFormatter;

class WalletReachedThreshold extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(protected WalletNotification $walletNotification)
    {

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
        $url = Config::get('front-end.prod.base_url').'/wallet';

        return (new MailMessage)
            ->greeting(__('Hello').'!')
            ->subject(__('emails/wallet-reached-threshold.'.$this->walletNotification->type->value.'.subject'))
            ->line(trans('emails/wallet-reached-threshold.'.$this->walletNotification->type->value.'.content', [
                'value' => $this->walletNotification->type->is(WalletNotificationType::ORDER_COUNT)
                    ? $this->walletNotification->value->format(style: NumberFormatter::TYPE_INT32)
                    : $this->walletNotification->value->convertAndFormatByDecimal(sperator: ','),
            ]))
            ->action(trans('emails/wallet-reached-threshold.action'), $url);
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
