<?php

namespace App\Notifications\FinancingOrders;

use App\Enums\SystemNotificationType;
use App\Models\TraderOrder;
use App\Notifications\BaseNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class TraderRequestExpired extends BaseNotification implements ShouldQueue
{
    public function __construct(private readonly TraderOrder $traderOrder) {}

    /**
     * Get the notification's type.
     * This should be a value from SystemNotificationType enum.
     */
    public function getType(): SystemNotificationType
    {
        return SystemNotificationType::TRADE_REQUEST_EXPIRED;
    }

    /**
     * Get the title for the notification.
     *
     * @param  mixed  $notifiable
     */
    public function getTitle($notifiable): string
    {
        return __('notification-types.trade_request_expired.label');
    }

    /**
     * Get the description for the notification.
     */
    public function getDescription($notifiable): string
    {
        return __('notification-types.trade_request_expired.description', [
            'trader_order_id' => $this->traderOrder->id,
            'order_id' => $this->traderOrder->financing_order_id,
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
            ->subject(__('emails/trader-request-expired.subject', [
                'trader_order_id' => $this->traderOrder->id,
                'order_id' => $this->traderOrder->financing_order_id,
            ]))
            ->line(__('emails/trader-request-expired.body', [
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
        ];
    }
}
