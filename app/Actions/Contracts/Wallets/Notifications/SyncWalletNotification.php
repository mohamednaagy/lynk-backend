<?php

namespace App\Actions\Contracts\Wallets\Notifications;

use App\Models\Company;
use App\Models\WalletNotification;

interface SyncWalletNotification
{
    public function handle(Company $company, array $data): ?WalletNotification;
}
