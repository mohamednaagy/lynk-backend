<?php

namespace App\Notifications\FinancingOrders\TraderOrders;

use App\Enums\FinancingOrderStatus;
use App\Models\FinancingOrder;
use App\Models\TraderOrder;
use App\Support\FinancingOrders\StepAndHistories\StepHistoriesDictionary;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TraderOrderProgressStopped extends Notification
{
    use Queueable;

    private FinancingOrder $financingOrder;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(private TraderOrder $traderOrder)
    {
        $this->financingOrder = $this->traderOrder->order->fresh();
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via(mixed $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return MailMessage
     */
    public function toMail(mixed $notifiable): MailMessage
    {
        $nextStepNode = app(StepHistoriesDictionary::class)->getNextStepOf($this->financingOrder->status->value);

        return (new MailMessage)
            ->subject(__('emails/trader-order-stopped.subject', [
                'order_id' => $this->traderOrder->id,
            ]))
            ->line(__('emails/trader-order-stopped.body', [
                'order_id' => $this->traderOrder->id,
                'next_step' => FinancingOrderStatus::fromValue($nextStepNode->status)->description,
            ]));
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
            //
        ];
    }
}
