<?php

namespace App\Notifications\FinancingOrders;

use App\Enums\SystemNotificationType;
use App\Models\FinancingOrder;
use App\Models\User;
use App\Notifications\BaseNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Config;

final class OrderRequiresApproval extends BaseNotification implements ShouldQueue
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
        return SystemNotificationType::ORDER_CREATED;
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
            ->subject(__('emails/order-requires-approval.subject', [
                'order_id' => $this->financingOrder->id,
            ]))
            ->line(__('emails/order-requires-approval.body', [
                'order_id' => $this->financingOrder->id,
            ]))
            ->action(__('emails/order-requires-approval.action'), $url);
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
