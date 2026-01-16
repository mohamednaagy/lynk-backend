<?php

namespace App\Notifications;

use App\Enums\SystemNotificationType;
use App\Models\Lender;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class LenderRegistered extends BaseNotification implements ShouldQueue
{
    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(private Lender $lender) {}

    /**
     * Get the notification's type.
     * This should be a value from SystemNotificationType enum.
     */
    public function getType(): SystemNotificationType
    {
        return SystemNotificationType::LENDER_REGISTERED;
    }

    /**
     * Get the title for the notification.
     *
     * @param  mixed  $notifiable
     */
    public function getTitle($notifiable): string
    {
        return __('notification-types.lender_registered.label');
    }

    /**
     * Get the description for the notification.
     *
     * @param  mixed  $notifiable
     */
    public function getDescription($notifiable): string
    {
        return __('notification-types.lender_registered.description', ['company_name' => $this->lender->name]);
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     */
    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(trans('emails/lender-registered.subject', [
                'company_name' => $this->lender->name,
                'app_name' => config('app.name'),
            ]))
            ->greeting(__('Hello'))
            ->line(trans('emails/lender-registered.registered_message', [
                'company_name' => $this->lender->name,
                'status_description' => $this->lender->status->description,
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
            'company_id' => $this->lender->id,
            'company_name' => $this->lender->name,
            'registered_at' => $this->lender->created_at,
            'company_status' => $this->lender->status,
        ];
    }
}
