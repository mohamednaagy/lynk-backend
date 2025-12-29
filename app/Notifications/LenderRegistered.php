<?php

namespace App\Notifications;

use App\Models\Lender;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LenderRegistered extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(private Lender $lender) {}

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     */
    public function via($notifiable): array
    {
        return ['mail', 'database'];
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
            'company_id' => $this->lender->id,
            'company_name' => $this->lender->name,
            'registered_at' => $this->lender->created_at,
            'company_status' => $this->lender->status,
        ];
    }

    public function viaQueues()
    {
        return [
            'mail' => 'notifications',
        ];
    }
}
