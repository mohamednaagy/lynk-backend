<?php

namespace App\Support\Traits\Wallet;

use App\Models\Wallet;
use App\Support\Wallets\Contracts\WalletServiceInterface;
use Illuminate\Database\Eloquent\Model;

trait HasWallet
{
    private WalletServiceInterface $walletService;

    public function __construct()
    {
        $this->walletService = app(WalletServiceInterface::class);
    }

    public function getWallet(string $name, bool $lock = true)
    {
        if (! $lock) {
            $this->walletService->findByName($name);
        }

        return $this->walletService->findByName($name)->lockForUpdate()->first();
    }

    public function getWalletOrFail(string $name, bool $lock = true)
    {
        if (! $lock) {
            $this->walletService->findByName($name);
        }

        return $this->walletService->findByNameOrFail($name)->lockForUpdate()->first();
    }

    public function getWallets(bool $lock = true)
    {
        if (! $lock) {
            Wallet::all();
        }

        return Wallet::lockForUpdate()->get();
    }

    public function hasWallet(string $name): bool
    {
        return (bool) $this->walletService->findByName($name);
    }

    public function createWallet(string $name, Model $model, bool $lock = true)
    {
        return $this->walletService->create($model, [
            'name' => $name,
        ]);
    }

    public function getBalance(string $name)
    {
        // should i add balance column in wallets table or there is another way
        // something like  calc it from transactions
        $wallet = $this->walletService->findByName($name);

        return $wallet;
    }

    public function deleteWallet(string $name): bool
    {
        return (bool) $this->walletService->findByName($name)->delete();
    }
}
