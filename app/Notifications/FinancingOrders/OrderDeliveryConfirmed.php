<?php

namespace App\Notifications\FinancingOrders;

use App\Models\TraderOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrderDeliveryConfirmed extends Notification implements ShouldQueue
{
    use Queueable;

    private TraderOrder $traderOrder;

    /**
     * Create a new notification instance.
     */
    public function __construct(TraderOrder $traderOrder)
    {
        $this->traderOrder = $traderOrder;
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
