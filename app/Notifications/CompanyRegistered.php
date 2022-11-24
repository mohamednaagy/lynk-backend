<?php

namespace App\Notifications;

use App\Models\Company;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CompanyRegistered extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(private Company $company)
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
            ->subject(trans('emails/company-registered.subject', [
                'companyName' => $this->company->getOriginal('name'),
                'appName' => config('app.name'),
            ]))
            ->greeting(trans('emails/company-registered.greeting'))
            ->line(trans('emails/company-registered.registered_message', [
                'companyName' => $this->company->getOriginal('name'),
                'statusDescription' => $this->company->status->description,
            ]))
            ->action(
                trans('emails/company-registered.view_information'),
                url('/api/v1/admin/companies/'.$this->company->getOriginal('id'))
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
            'company_id' => $this->company->id,
            'company_name' => $this->company->name,
            'registered_at' => now(),
        ];
    }
}
