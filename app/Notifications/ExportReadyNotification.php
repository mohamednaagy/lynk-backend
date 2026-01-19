<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Enums\SystemNotificationType;
use App\Jobs\Reports\Enums\ReportType;
use App\Models\Media;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Str;
use InvalidArgumentException;

class ExportReadyNotification extends BaseNotification implements ShouldQueue
{
    private ?Media $media = null;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(
        private readonly string $exportType,
        private readonly string $mediaId,
    ) {
        $this->media = Media::find($this->mediaId);

        if (! $this->media) {
            throw new InvalidArgumentException("Media with ID {$this->mediaId} does not exist.");
        }
    }

    /**
     * Get the export type
     */
    public function getExportType(): string
    {
        return $this->exportType;
    }

    /**
     * Get the media ID
     */
    public function getMediaId(): string
    {
        return $this->mediaId;
    }

    /**
     * Get the notification's type.
     * This should be a value from SystemNotificationType enum.
     */
    public function getType(): SystemNotificationType
    {
        return SystemNotificationType::ORDERS_REPORT_EXPORT_READY;
    }

    /**
     * Get the title for the notification.
     *
     * @param  mixed  $notifiable
     */
    public function getTitle($notifiable): string
    {
        $prefix = $this->getReportName();

        return ($prefix ? "{$prefix} " : '').__('notification-types.orders_report_export_ready.label');
    }

    /**
     * Get the description for the notification.
     *
     * @param  mixed  $notifiable
     */
    public function getDescription($notifiable): string
    {
        return __('notification-types.orders_report_export_ready.description', [
            'exportType' => $this->getReportName() ?: $this->exportType,
        ]);
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     */
    public function toMail($notifiable): MailMessage
    {
        $reportName = $this->getReportName();

        return (new MailMessage)
            ->subject(__("Your {$reportName} Export is Ready"))
            ->greeting(__('Hello'))
            ->line(__("Your {$reportName} export has been completed and is ready for download."))
            ->action(__('Download Export'), formatMediaUrl($this->media?->fileUrl))
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
            'download_url' => formatMediaUrl($this->media?->fileUrl),
            'file_name' => $this->media->file_name,
        ];
    }

    private function getReportName(): string
    {
        return match (Str::convertCase($this->exportType)) {
            ReportType::OrderList => 'Order List',
            default => ''
        };
    }
}
