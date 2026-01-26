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
     * Get the title for the notification.
     *
     * @param  mixed  $notifiable
     */
    public function getTitle($notifiable): string
    {
        return __('notification-types.delivery_confirmation_received.label');
    }

    /**
     * Get the description for the notification.
     *
     * @param  mixed  $notifiable
     */
    public function getDescription($notifiable): string
    {
        return __('notification-types.delivery_confirmation_received.description', [
            'order_id' => $this->financingOrder->id,
            'trader_reference' => $this->traderOrder->reference,
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
        $order = $this->traderOrder->order;

        return (new MailMessage)
            ->subject(__('emails/order-delivery-confirmed.subject', [
                'order_id' => $order->id,
                'company_name' => $order->lender->name,
            ]))
            ->markdown('emails.order-delivery-confirmed', [
                'name' => $notifiable->fullName,
                'order_id' => $order->id,
                'trader_reference' => $this->traderOrder->reference,
                'company_name' => $this->traderOrder->order->lender->name,
            ]);
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
            'order_id' => $this->financingOrder->id,
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
