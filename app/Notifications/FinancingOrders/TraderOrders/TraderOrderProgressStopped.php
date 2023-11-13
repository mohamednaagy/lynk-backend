<?php

namespace App\Notifications\FinancingOrders\TraderOrders;

use App\Enums\MurabhaStep;
use App\Models\TraderOrder;
use App\Support\FinancingOrders\StepAndHistories\StepHistoriesDictionary;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TraderOrderProgressStopped extends Notification implements ShouldQueue
{
    use Queueable;

    private StepHistoriesDictionary $traderDictionary;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(private TraderOrder $traderOrder)
    {
        $this->traderDictionary = new StepHistoriesDictionary($traderOrder->provider, $traderOrder->version);
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via(mixed $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(mixed $notifiable): MailMessage
    {
        $currentStepNode = $this->traderDictionary->getStepByHistory($this->traderOrder->last_history_action);
        $nextStepNode = $this->traderDictionary->getNextStepOf($currentStepNode->step);
        $nextStepEnum = $nextStepNode
            ? MurabhaStep::fromValue($nextStepNode->step)->description
            : null;

        return (new MailMessage)
            ->subject(__('emails/trader-order-stopped.subject', [
                'order_id' => $this->traderOrder->financing_order_id,
            ]))
            ->line(__('emails/trader-order-stopped.body', [
                'order_id' => $this->traderOrder->financing_order_id,
                'next_step' => $nextStepEnum,
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
            'order_id' => $this->traderOrder->financing_order_id,
            'time' => now(),

        ];
    }

    public function viaQueues()
    {
        return [
            'mail' => 'notifications',
        ];
    }
}
