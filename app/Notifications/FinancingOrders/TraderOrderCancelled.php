<?php

namespace App\Notifications\FinancingOrders;

use App\Models\TraderOrder;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TraderOrderCancelled extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(private TraderOrder $traderOrder, private User $user)
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
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject(__('emails/trader-order-cancelled.subject', [
                'trader_order_id' => $this->traderOrder->id,
                'order_id' => $this->traderOrder->financing_order_id,
            ]))
            ->line(__('emails/trader-order-cancelled.body', [
                'trader_order_id' => $this->traderOrder->id,
                'order_id' => $this->traderOrder->financing_order_id,
            ]));
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
            'trader_order_id' => $this->traderOrder->id,
            'order_id' => $this->traderOrder->financing_order_id,
            'amount' => $this->traderOrder->order->amount,
            'selling_price' => $this->traderOrder->order->selling_price,
            'user_id' => $this->user->id,
            'user_name' => $this->user->fullName,
        ];
    }

    public function viaQueues()
    {
        return [
            'mail' => 'notifications',
        ];
    }
}
