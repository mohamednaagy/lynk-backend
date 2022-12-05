<?php

namespace App\Notifications;

use App\Models\Company;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

class LenderRegistered extends Notification
{
    use Queueable;

    private $url;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(private Company $company, string $externalUrl)
    {
        $this->url = URL::signedExternalRoute(
            $externalUrl,
            'api.v1.admins.companies.show',
            ['company' => $this->company->id]
        );
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
            ->subject(trans('emails/lender-registered.subject', [
                'company_name' => $this->company->name,
                'app_name' => config('app.name'),
            ]))
            ->greeting(__('Hello'))
            ->line(trans('emails/lender-registered.registered_message', [
                'company_name' => $this->company->name,
                'status_description' => $this->company->status->description,
            ]))
            ->action(
                trans('emails/lender-registered.view_information'),
                $this->url
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
            'registered_at' => $this->company->created_at,
        ];
    }
}
