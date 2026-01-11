<?php

namespace App\Notifications\FinancingOrders;

use App\Enums\SystemNotificationType;
use App\Models\FinancingOrder;
use App\Models\User;
use App\Notifications\BaseNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class OrderCancelled extends BaseNotification implements ShouldQueue
{
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
     * Get the notification's type.
     * This should be a value from SystemNotificationType enum.
     */
    public function getType(): SystemNotificationType
    {
        return SystemNotificationType::ORDER_CANCELLED;
    }

    /**
     * Get the title for the notification.
     *
     * @param  mixed  $notifiable
     */
    public function getTitle($notifiable): string
    {
        return __('notification-types.order_cancelled.label');
    }

    /**
     * Get the description for the notification.
     *
     * @param  mixed  $notifiable
     */
    public function getDescription($notifiable): string
    {
        return __('notification-types.order_cancelled.description', [
            'order_id' => $this->financingOrder->id,
            'user_name' => $this->user->fullName,
            'amount' => $this->financingOrder->amount,
            'selling_price' => $this->financingOrder->selling_price,
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
            ->subject(__('emails/order-cancelled.subject', [
                'order_id' => $this->financingOrder->id,
            ]))
            ->line(__('emails/order-cancelled.body', [
                'order_id' => $this->financingOrder->id,
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
            'order_id' => $this->financingOrder->id,
            'amount' => $this->financingOrder->amount,
            'selling_price' => $this->financingOrder->selling_price,
            'user_id' => $this->user->id,
            'user_name' => $this->user->fullName,
        ];
    }
}
