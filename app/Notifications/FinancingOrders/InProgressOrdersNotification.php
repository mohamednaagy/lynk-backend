<?php

namespace App\Notifications\FinancingOrders;

use App\Enums\FinancingOrderStatus;
use App\Enums\NotificationChannel;
use App\Enums\SystemNotificationType;
use App\Models\Company;
use App\Notifications\BaseNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Config;

final class InProgressOrdersNotification extends BaseNotification implements ShouldQueue
{
    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(
        private readonly int $companyId,
    ) {}

    /**
     * Get the notification's type.
     */
    public function getType(): SystemNotificationType
    {
        return SystemNotificationType::IN_PROGRESS_ORDERS;
    }

    /**
     * Determine which channels the notification should be delivered on.
     *
     * When we send this notification using Notification::route() with an
     * AnonymousNotifiable (a list of raw email addresses), we cannot rely
     * on the BaseNotification role / preference checks, because there is
     * no actual User model instance. In that case we just send via mail.
     *
     * @param  mixed  $notifiable
     */
    public function via($notifiable): array
    {
        if ($notifiable instanceof AnonymousNotifiable) {
            // Preferences & roles were already filtered at the query level
            return [NotificationChannel::MAIL->value];
        }

        return parent::via($notifiable);
    }

    /**
     * Get the title for the notification.
     *
     * @param  mixed  $notifiable
     */
    public function getTitle($notifiable): string
    {
        return __('notification-types.in_progress_orders.label');
    }

    /**
     * Get the description for the notification.
     *
     * @param  mixed  $notifiable
     */
    public function getDescription($notifiable): string
    {
        return __('notification-types.in_progress_orders.description', [
            'company_name' => $this->getCompanyName(),
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
            ->bcc($this->getBccUsers())
            ->subject(__('emails/in-progress-orders.subject'))
            ->greeting(__('emails/in-progress-orders.greeting', ['company_name' => $this->getCompanyName()]))
            ->line(__('emails/in-progress-orders.body'))
            ->action(__('emails/in-progress-orders.action'), $this->getActionURL());
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
            'company_id' => $this->companyId,
            'company_name' => $this->getCompanyName(),
        ];
    }

    protected function getActionURL(): string
    {
        $url = Config::get('app.frontend_url.lender').'/orders';

        $query = http_build_query([
            'direction' => 'desc',
            'sort' => 'id',
            'charged_transactions' => 1,
            'status' => FinancingOrderStatus::InProgress,
            'page' => 1,
        ]);

        return $url.'?'.$query;
    }

    private function getCompanyName(): string
    {
        $company = Company::withoutGlobalScopes()->find($this->companyId);

        return $company?->name ?? (string) $this->companyId;
    }
}
