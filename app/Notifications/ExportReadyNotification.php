<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Enums\SystemNotificationType;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class ExportReadyNotification extends BaseNotification implements ShouldQueue
{
    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(
        private string $exportType,
        private string $downloadUrl,
        private string $fileName
    ) {}

    /**
     * Get the notification's type.
     * This should be a value from SystemNotificationType enum.
     */
    public function getType(): SystemNotificationType
    {
        return SystemNotificationType::EXPORT_READY;
    }

    /**
     * Get the title for the notification.
     *
     * @param  mixed  $notifiable
     */
    public function getTitle($notifiable): string
    {
        return __('notification-types.export_ready.label');
    }

    /**
     * Get the description for the notification.
     *
     * @param  mixed  $notifiable
     */
    public function getDescription($notifiable): string
    {
        return __('notification-types.export_ready.description', [
            'exportType' => $this->exportType,
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
            ->subject(__("Your {$this->exportType} Export is Ready"))
            ->greeting(__('Hello'))
            ->line(__("Your {$this->exportType} export has been completed and is ready for download."))
            ->action(__('Download Export'), $this->downloadUrl)
            ->line(__('Thank you for using our service!'));
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
            'export_type' => $this->exportType,
            'url' => $this->downloadUrl,
            'file_name' => $this->fileName,
        ];
    }
}
