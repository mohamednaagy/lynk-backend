<?php

namespace App\Notifications\FinancingOrders\TraderOrders;

use App\Enums\BursamMurabhaStep;
use App\Enums\DmccMurabhaStep;
use App\Models\FinancingOrder;
use App\Models\TraderOrder;
use App\Support\FinancingOrders\StepAndHistories\StepHistoriesDictionary;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Stancl\Tenancy\Database\TenantScope;

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
        $this->financingOrder = $this->traderOrder
            ->order()
            ->withoutGlobalScope(TenantScope::class)
            ->first();
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
        $traderOrder = $this->traderOrder->withLastHistoryAction()->latest()->first();
        $currentStepNode = app(StepHistoriesDictionary::class)->getStepByHistory($traderOrder->last_history_action);
        $nextStepNode = app(StepHistoriesDictionary::class)->getNextStepOf($currentStepNode->step);
        $nextStepDescription = $nextStepNode ? $nextStepNode->step : null;
        $nextStepEnum = $this->getDescriptionFromEnumMurabhaStep($nextStepDescription, BursamMurabhaStep::class, DmccMurabhaStep::class);

        return (new MailMessage)
            ->subject(__('emails/trader-order-stopped.subject', [
                'order_id' => $this->traderOrder->id,
            ]))
            ->line(__('emails/trader-order-stopped.body', [
                'order_id' => $this->traderOrder->id,
                'next_step' => $nextStepEnum,
            ]));
    }

    public function getDescriptionFromEnumMurabhaStep($description, ...$enumClasses)
    {
        foreach ($enumClasses as $enumClass) {
            $enumValue = $enumClass::getDescriptionFromEnum($description);
            if ($enumValue) {
                return $enumValue;
            }
        }

        return null;
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
            'order_id' => $this->traderOrder->id,
            'time' => now(),

        ];
    }
}
