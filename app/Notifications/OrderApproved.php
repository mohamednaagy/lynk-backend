<?php

namespace App\Notifications;

use App\Enums\SystemNotificationType;
use App\Models\FinancingOrder;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class OrderApproved extends BaseNotification implements ShouldQueue
{
    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(
        private FinancingOrder $financingOrder,
        private User $approver,
        private Carbon $approvalTime
    ) {}

    /**
     * Get the notification's type.
     * This should be a value from SystemNotificationType enum.
     */
    public function getType(): SystemNotificationType
    {
        return SystemNotificationType::ORDER_APPROVED;
    }

    /**
     * Get the title for the notification.
     *
     * @param  mixed  $notifiable
     */
    public function getTitle($notifiable): string
    {
        return __('notification-types.order_approved.label');
    }

    /**
     * Get the description for the notification.
     *
     * @param  mixed  $notifiable
     */
    public function getDescription($notifiable): string
    {
        return __('notification-types.order_approved.description', [
            'order_id' => $this->financingOrder->id,
            'approver_name' => $this->approver->full_name,
            'approved_at' => $this->approvalTime->toDateTimeString(),
        ]);
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
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
     */
    public function toArray($notifiable): array
    {
        return [
            ...parent::toArray($notifiable),
            'approver_id' => $this->approver->id,
            'approver_name' => $this->approver->full_name,
            'approved_at' => $this->approvalTime,
            'order_id' => $this->financingOrder->id,
        ];
    }
}
