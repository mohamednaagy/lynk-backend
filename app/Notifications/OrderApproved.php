<?php

namespace App\Notifications;

use App\Models\FinancingOrder;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

class OrderApproved extends Notification
{
    use Queueable;

    private Carbon $approvalTime;

    private $url;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(private FinancingOrder $financingOrder, private User $approver, string $externalUrl)
    {
        $this->approvalTime = new Carbon();
        $this->url = URL::signedExternalRoute(
            $externalUrl,
            'api.v1.orders.show',
            ['order' => $this->financingOrder->id]
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
            ->subject(trans('emails/order-approved.subject', [
                'order_id' => $this->financingOrder->id,
            ]))
            ->greeting(__('Hello'))
            ->line(trans('emails/order-approved.approved_message', [
                'order_id' => $this->financingOrder->id,
                'approved_at' => $this->approvalTime->toDateTimeString(),
            ]))
            ->action(
                trans('emails/order-approved.view_order'),
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
            'approver_id' => $this->approver->id,
            'approver_name' => $this->approver->full_name,
            'approved_at' => $this->approvalTime,
        ];
    }
}
