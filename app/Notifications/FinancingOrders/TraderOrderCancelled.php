<?php

namespace App\Notifications\FinancingOrders;

use App\Enums\SystemNotificationType;
use App\Models\TraderOrder;
use App\Models\User;
use App\Notifications\BaseNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class TraderOrderCancelled extends BaseNotification implements ShouldQueue
{
    public function __construct(private readonly TraderOrder $traderOrder, private readonly ?User $user = null) {}

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
        return __('notification-types.trade_request_cancelled.label');
    }

    /**
     * Get the description for the notification.
     */
    public function getDescription($notifiable): string
    {
        return __('notification-types.trade_request_cancelled.description', [
            'trader_order_id' => $this->traderOrder->id,
            'order_id' => $this->traderOrder->financing_order_id,
            'user_name' => $this->user->fullName ?? 'System',
            'amount' => $this->traderOrder->order->amount,
            'selling_price' => $this->traderOrder->order->selling_price,
        ]);
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return MailMessage
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
     */
    public function toArray($notifiable): array
    {
        return [
            ...parent::toArray($notifiable),
            'trader_order_id' => $this->traderOrder->id,
            'order_id' => $this->traderOrder->financing_order_id,
            'amount' => $this->traderOrder->order->amount,
            'selling_price' => $this->traderOrder->order->selling_price,
            'user_id' => $this->user->id ?? null,
            'user_name' => $this->user->fullName ?? 'System',
        ];
    }
}
