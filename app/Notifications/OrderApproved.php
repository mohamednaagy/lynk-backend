<?php

namespace App\Notifications;

use App\Models\FinancingOrder;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrderApproved extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(
        private FinancingOrder $financingOrder,
        private User $approver,
        private Carbon $approvalTime
    ) {
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
                'order_id' => $this->financingOrder->id,
            ]))
            ->greeting(__('Hello'))
            ->line(trans('emails/order-approved.approved_message', [
                'order_id' => $this->financingOrder->id,
                'approved_at' => $this->approvalTime->toDateTimeString(),
            ]));
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
            'approved_at' => $this->approvalTime,
            'order_id' => $this->financingOrder->id,
        ];
    }
}
