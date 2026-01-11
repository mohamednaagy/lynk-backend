<?php

namespace App\Notifications\FinancingOrders;

use App\Enums\SystemNotificationType;
use App\Models\TraderOrder;
use App\Notifications\BaseNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class TraderOrderExpired extends BaseNotification implements ShouldQueue
{
    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(private TraderOrder $traderOrder)
    {
        //
    }

    /**
     * Get the notification's type.
     * This should be a value from SystemNotificationType enum.
     */
    public function getType(): SystemNotificationType
    {
        return SystemNotificationType::TRADE_REQUEST_CANCELLED;
    }

    /**
     * Get the title for the notification.
     *
     * @param  mixed  $notifiable
     */
    public function getTitle($notifiable): string
    {
        return __('Trade Request Expired');
    }

    /**
     * Get the description for the notification.
     *
     * @param  mixed  $notifiable
     */
    public function getDescription($notifiable): string
    {
        return __('Trade request #:trader_order_id for order #:order_id has expired. Trade request ID: :trader_order_id, Order ID: :order_id', [
            'trader_order_id' => $this->traderOrder->id,
            'order_id' => $this->traderOrder->financing_order_id,
        ]);
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
            ...parent::toArray($notifiable),
            'trader_order_id' => $this->traderOrder->id,
            'order_id' => $this->traderOrder->financing_order_id,
        ];
    }
}
