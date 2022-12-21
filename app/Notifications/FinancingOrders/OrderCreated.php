<?php

namespace App\Notifications\FinancingOrders;

use App\Models\FinancingOrder;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Config;

class OrderCreated extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(private FinancingOrder $financingOrder, private User $user)
    {
        //
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
     * @return MailMessage
     */
    public function toMail($notifiable)
    {
        $url = Config::get('front-end.prod.base_url').'/orders/'.$this->financingOrder->id;

        return (new MailMessage)
            ->subject(__('emails/order-created.subject', [
                'order_id' => $this->financingOrder->id,
            ]))
            ->line(__('emails/order-created.body', [
                'order_id' => $this->financingOrder->id,
            ]))
            ->action(__('emails/order-created.action'), $url);
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
            'order_id' => $this->financingOrder->id,
            'amount' => $this->financingOrder->amount,
            'selling_price' => $this->financingOrder->selling_price,
            'user_id' => $this->user->id,
            'user_name' => $this->user->fullName,
        ];
    }
}
