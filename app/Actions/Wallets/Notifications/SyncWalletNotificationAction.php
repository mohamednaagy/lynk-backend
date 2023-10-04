<?php

namespace App\Actions\Wallets\Notifications;

use App\Actions\Contracts\Wallets\Notifications\SyncWalletNotification;
use App\Enums\WalletType;
use App\Models\Company;
use App\Models\WalletNotification;
use Cknow\Money\Money;
use Illuminate\Support\Arr;

class SyncWalletNotificationAction implements SyncWalletNotification
{
    public function handle(Company $company, array $data): ?WalletNotification
    {
        if (is_null($data['value'])) {
            $company->walletNotification()->delete();

            return null;
        }

        $wallet = $company->getWallet(WalletType::CompanyWallet);

        $data['value'] = Money::parseByDecimal($data['value'], $wallet->currency);

        /** @var WalletNotification $walletNotification */
        $walletNotification = $company->walletNotification()
            ->updateOrCreate(['wallet_id' => $wallet->id],
                Arr::only($data, ['type', 'value']) + [
                    'wallet_id' => $wallet->id,
                ]
            );

        if ($walletNotification->wasChanged(['value', 'type'])) {
            $walletNotification->markAsNotNotified();
        }

        return $walletNotification;
    }
}
