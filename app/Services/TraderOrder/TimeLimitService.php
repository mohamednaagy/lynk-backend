<?php

namespace App\Services\TraderOrder;

use App\Enums\TraderOrderTimeLimitStatus;
use App\Enums\TraderOrderTimeLimitType;
use App\Models\TraderOrder;
use Carbon\Carbon;
use App\Settings\Classes\LocalMurabahaSettings;
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
        $traderOrder->timeLimits()->create([
            'type' => $type,
            'effective_at' => $effectiveAt,
            'default_value' => $defaultValue,
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
        $config = $this->getContractSignedLimitTimeConfig();
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
     * @param TraderOrder $traderOrder The trader order for which to set the delivery confirmation time limit.
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
     * @param TraderOrder $traderOrder The TraderOrder for which to remove the expiration job.
     *
     * @return void
     */
    public function cancelExpiry(TraderOrder $traderOrder)
    {
        removeJobFromQueue('expire-trader-order', $traderOrder->id);
        $timeLimit = $traderOrder->timeLimits()->where('status', TraderOrderTimeLimitStatus::Pending)->latest()->first();

        if ($timeLimit) {
            $timeLimit->cancel();
            Log::info("Cancelled scheduled expiration job for Trader Order ID: {$traderOrder->id}");
        }
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
        $defaultValue = app(LocalMurabahaSettings::class)->default_customer_delivery_confirmation_time_limit ?? 72;
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
    private function getContractSignedLimitTimeConfig()
    {
        $defaultValue = app(LocalMurabahaSettings::class)->default_contract_sign_time_limit;
        $effectiveAt = Carbon::now()
            ->timezone('UTC')
            ->addHours($defaultValue)
            ->format('Y-m-d H:i:s');
        return ['default_value' => $defaultValue, 'effective_at' => $effectiveAt];
    }
}
