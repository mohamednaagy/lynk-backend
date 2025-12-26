<?php

namespace App\Notifications\FinancingOrders;

use App\Enums\NotificationChannel;
use App\Enums\SystemNotificationType;
use App\Models\FinancingOrder;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Config;

final class OrderCreated extends Notification implements ShouldQueue
{
    use Queueable;

    private const TYPE = SystemNotificationType::ORDER_CREATED;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(private FinancingOrder $financingOrder, private User $user)
    {
        //
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        $channels = [];

        // Get the user's notification settings for this type
        // Use the already loaded relationship if available to avoid N+1 queries
        $settings = $notifiable->relationLoaded('notificationSettings')
            ? $notifiable->notificationSettings->where('notification_type', self::TYPE)
            : $notifiable->notificationSettings()
                ->where('notification_type', self::TYPE)
                ->get();

        // Check if platform notification is enabled
        $platformSetting = $settings->where('channel', NotificationChannel::PLATFORM)->first();
        if ($platformSetting && $platformSetting->is_enabled) {
            $channels[] = 'database';
        }

        // Check if mail is enabled
        $mailSetting = $settings->where('channel', NotificationChannel::MAIL)->first();
        if ($mailSetting && $mailSetting->is_enabled) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return MailMessage
     */
    public function toMail($notifiable)
    {
        $url = Config::get('front-end.prod.base_url').'/orders/'.$this->financingOrder->id;

        return (new MailMessage)
            ->subject(__('emails/order-created.subject', [
                'order_id' => $this->financingOrder->id,
            ]))
            ->line(__('emails/order-created.body', [
                'order_id' => $this->financingOrder->id,
            ]))
            ->action(__('emails/order-created.action'), $url);
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            'order_id' => $this->financingOrder->id,
            'amount' => $this->financingOrder->amount,
            'selling_price' => $this->financingOrder->selling_price,
            'user_id' => $this->user->id,
            'user_name' => $this->user->fullName,
        ];
    }

    public function viaQueues()
    {
        return [
            'mail' => 'notifications',
        ];
    }
}
