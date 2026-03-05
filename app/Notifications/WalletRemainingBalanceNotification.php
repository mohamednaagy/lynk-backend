<?php

namespace App\Notifications;

use App\Enums\SystemNotificationType;
use App\Models\Lender;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class WalletRemainingBalanceNotification extends BaseNotification implements ShouldQueue
{
    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(
        private readonly Lender $lender,
        private readonly float $balance,
    ) {}

    /**
     * Get the notification's type.
     * This should be a value from SystemNotificationType enum.
     */
    public function getType(): SystemNotificationType
    {
        return SystemNotificationType::WALLET_REMAINING_BALANCE;
    }

    public function via($notifiable): array
    {
        if (($this->lender->lenderDetail->min_wallet_limit ?? 0) <= 0) {
            return [];
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
        return __('notification-types.wallet_remaining_balance.label');
    }

    /**
     * Get the description for the notification.
     *
     * @param  mixed  $notifiable
     */
    public function getDescription($notifiable): string
    {
        return __('notification-types.wallet_remaining_balance.description', [
            'company_id' => $this->lender->id,
            'balance' => $this->balance,
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
            ->subject(trans('emails/wallet-remaining-balance.subject'))
            ->markdown('emails/wallet-remaining-balance', [
                'company_name' => $this->lender->name,
                'balance' => $this->balance,
                'remaining_balance_limit' => $this->lender->lenderDetail->min_wallet_limit,
            ]);
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
            'lender_id' => $this->lender->id,
            'balance' => $this->balance,
            'remaining_balance_limit' => $this->lender->lenderDetail->min_wallet_limit,
        ];
    }
}
