<?php

namespace App\Services\TraderOrder;

use App\Enums\Trader;
use App\Enums\TraderOrderTimeLimitAction;
use App\Enums\TraderOrderTimeLimitStatus;
use App\Enums\TraderOrderTimeLimitType;
use App\Models\Company;
use App\Models\TraderOrder;
use App\Settings\Classes\LocalMurabahaSettings;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class TimeLimitService
{
    /**
     * Set a time limit for the given TraderOrder.
     *
     * @param  string  $effectiveAt  - Format: 'Y-m-d H:i:s', timezone: UTC
     * @param  int  $defaultValue  - minutes of hours
     */
    private function setTimeLimit(TraderOrder $traderOrder, int $type, string $effectiveAt, int $defaultValue): void
    {
        // Determine the appropriate action based on the trader order's mode and provider
        $action = $this->determineAction($traderOrder);
        $traderOrder->timeLimits()->create([
            'type' => $type,
            'effective_at' => $effectiveAt,
            'default_value' => $defaultValue,
            'action' => $action,
        ]);
    }

    /**
     * Set the contract sign time limit.
     *
     * @param  string  $effectiveAt  - Format: 'Y-m-d H:i:s', timezone: UTC
     * @param  int  $defaultValue  - minutes of hours
     */
    public function setContractSignTimeLimit(TraderOrder $traderOrder): void
    {
        $company = $traderOrder->order->company;
        $config = $this->getContractSignedLimitTimeConfig($company, $traderOrder->provider);
        $this->setTimeLimit(
            $traderOrder,
            TraderOrderTimeLimitType::ContractSignTimeLimit,
            $config['effective_at'],
            $config['default_value']
        );
    }

    /**
     * Set the delivery confirmation time limit for the given TraderOrder.
     *
     * Retrieves the default delivery confirmation time limit from the settings,
     * calculates the effective time by adding the default limit to the current time,
     * and sets the time limit in the TraderOrder.
     *
     * @param  TraderOrder  $traderOrder  The trader order for which to set the delivery confirmation time limit.
     */
    public function setDeliveryConfirmationTimeLimit(TraderOrder $traderOrder): void
    {
        $config = $this->getConfirmDeliveryTimeConfig();
        $this->setTimeLimit(
            $traderOrder,
            TraderOrderTimeLimitType::DeliveryConfirmationTimeLimit,
            $config['effective_at'],
            $config['default_value']
        );
    }

    /**
     * Removes the scheduled expiration job for the given TraderOrder, and cancels
     * the most recent pending time limit.
     *
     * @param  TraderOrder  $traderOrder  The TraderOrder for which to remove the expiration job.
     * @param  int  $timeLimitType  The type of time limit to cancel.
     * @return void
     */
    public function cancelExpiry(TraderOrder $traderOrder, int $timeLimitType)
    {
        // Remove the job from the queue
        removeJobFromQueue('expire-trader-order', $traderOrder->id);

        // Retrieve and cancel the pending time limit
        $timeLimit = $traderOrder->timeLimits()
            ->where('status', TraderOrderTimeLimitStatus::Pending)
            ->where('type', $timeLimitType)
            ->where('action', TraderOrderTimeLimitAction::AutoCancelOrder)
            ->first(); // Ensure we get a single instance

        if ($timeLimit) {
            $timeLimit->cancel(); // Call the model's cancel method
            Log::channel(getSuitableLoggingFromTraderProvider($traderOrder))->info(formatLogTitle('Cancelled scheduled expiration job for Trader Order ID: ', $traderOrder));
        } else {
            Log::channel(getSuitableLoggingFromTraderProvider($traderOrder))->warning(formatLogTitle('No pending time limit found to cancel for Trader Order ID: ', $traderOrder));
        }
    }

    /**
     * Cancel all pending time limits for the given TraderOrder.
     *
     * @param  TraderOrder  $traderOrder  The TraderOrder for which to cancel pending time limits.
     */
    public function cancelPendingTimeLimits(TraderOrder $traderOrder): void
    {
        $traderOrder->timeLimits()->where('status', TraderOrderTimeLimitStatus::Pending)->update([
            'status' => TraderOrderTimeLimitStatus::Canceled,
        ]);
    }

    /**
     * Get the delivery confirmation time configuration.
     *
     * Fetches the default delivery confirmation time limit from the local Murabaha settings,
     * calculates the effective delivery confirmation time by adding the default time limit to the current time in UTC,
     * and returns these values in an associative array.
     *
     * @return array An associative array containing 'default_value' (the default delivery confirmation time limit in hours)
     *               and 'effective_at' (the calculated effective delivery confirmation time as a string in 'Y-m-d H:i:s' format).
     */
    private function getConfirmDeliveryTimeConfig()
    {
        $defaultValue = (int) (app(LocalMurabahaSettings::class)->default_customer_delivery_confirmation_time_limit ?? 72);
        $effectiveAt = Carbon::now()
            ->timezone('UTC')
            ->addHours($defaultValue)
            ->format('Y-m-d H:i:s');

        return ['default_value' => $defaultValue, 'effective_at' => $effectiveAt];
    }

    /**
     * Get the contract sign time limit configuration.
     *
     * Fetches the default contract sign time limit from the local Murabaha settings,
     * calculates the effective contract sign time by adding the default time limit to the current time in UTC,
     * and returns these values in an associative array.
     *
     * @return array An associative array containing 'default_value' (the default contract sign time limit in hours)
     *               and 'effective_at' (the calculated effective contract sign time as a string in 'Y-m-d H:i:s' format).
     */
    private function getContractSignedLimitTimeConfig(Company $company, $provider): array
    {
        [$defaultValue, $effectiveAt] = match ($provider) {
            Trader::Bursam => [
                Carbon::now()->diffInHours(get_bursam_contract_signed_deadline()), // default value
                get_bursam_contract_signed_deadline(), // Effective time
            ],
            Trader::Lynk => [
                ($company->lender->lenderDetail->default_contract_sign_time_limit
                    ?? app(LocalMurabahaSettings::class)->default_contract_sign_time_limit), // default value
                Carbon::now()
                    ->timezone('UTC')
                    ->addHours(
                        ($company->lender->lenderDetail->default_contract_sign_time_limit
                            ?? app(LocalMurabahaSettings::class)->default_contract_sign_time_limit)
                    )
                    ->format('Y-m-d H:i:s'), // Effective time
            ],
        };

        return [
            'default_value' => $defaultValue,
            'effective_at' => $effectiveAt,
        ];
    }

    /**
     * Determine the action for the time limit based on the trader order.
     *
     * @param  TraderOrder  $traderOrder  The trader order instance.
     * @return int The action to be set.
     */
    private function determineAction(TraderOrder $traderOrder): int
    {
        // If mode is automatic and provider is Lynk, set to auto-cancel, otherwise no action needed
        return ($traderOrder->isAutomaticMode() && $traderOrder->provider === Trader::Lynk)
            ? TraderOrderTimeLimitAction::AutoCancelOrder
            : TraderOrderTimeLimitAction::NoActionNeeded;
    }
}
