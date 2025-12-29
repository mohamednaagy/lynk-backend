<?php

namespace App\Notifications\FinancingOrders;

use App\Enums\SystemNotificationType;
use App\Models\FinancingOrder;
use App\Models\TraderOrder;
use App\Notifications\BaseNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Notifications\Messages\MailMessage;

class OrderDeliveryConfirmed extends BaseNotification implements ShouldQueue
{
    private TraderOrder $traderOrder;

    private FinancingOrder $financingOrder;

    /**
     * Create a new notification instance.
     */
    public function __construct(TraderOrder $traderOrder)
    {
        $this->traderOrder = $traderOrder;
        $this->financingOrder = $traderOrder->order;
    }

    /**
     * Get the notification's type.
     * This should be a value from SystemNotificationType enum.
     */
    public function getType(): SystemNotificationType
    {
        return SystemNotificationType::DELIVERY_CONFIRMATION_RECEIVED;
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return MailMessage
     */
    public function toMail($notifiable)
    {
        $order = $this->traderOrder->order;

        return (new MailMessage)
            ->subject(__('emails/order-delivery-confirmed.subject', [
                'order_id' => $order->id,
                'company_name' => $order->company->name,
            ]))
            ->markdown('emails.order-delivery-confirmed', [
                'name' => $notifiable->fullName,
                'order_id' => $order->id,
                'trader_reference' => $this->traderOrder->reference,
                'company_name' => $this->traderOrder->order->company->name,
            ]);
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     */
    public function toArray($notifiable): array
    {
        return array_merge(parent::toArray($notifiable), [
            'trader_order_id' => $this->traderOrder->id,
            'order_id' => $this->financingOrder->id,
        ]);
    }

    /**
     * Specify which queue should handle which channels.
     *
     * @return array
     */
    public function viaQueues()
    {
        return [
            'mail' => 'notifications',
        ];
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.order-delivery-confirmed',
        );
    }
}
