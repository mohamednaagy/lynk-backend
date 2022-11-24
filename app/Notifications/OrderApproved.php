<?php

namespace App\Notifications;

use App\Models\FinancingOrder;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrderApproved extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(private FinancingOrder $financingOrder, private User $approver)
    {
        //
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return MailMessage
     */
    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(trans('emails/order-approved.subject', [
                'orderId' => $this->financingOrder->getOriginal('id'),
            ]))
            ->greeting(trans('emails/order-approved.greeting'))
            ->line(trans('emails/order-approved.approved_message', [
                'orderId' => $this->financingOrder->getOriginal('id'),
                'approvedAt' => now()->format('Y-m-d H:i:s'),
            ]))
            ->action(
                trans('emails/order-approved.view_order'),
                url('/api/v1/lender/orders/'.$this->financingOrder->getOriginal('id'))
            );
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable): array
    {
        return [
            'approver_id' => $this->approver->id,
            'approver_name' => $this->approver->full_name,
            'approved_at' => now(),
        ];
    }
}
