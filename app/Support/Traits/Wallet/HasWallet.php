<?php

namespace App\Support\Traits\Wallet;

use App\Support\Wallets\Contracts\WalletServiceInterface;
use Illuminate\Database\Eloquent\Model;

trait HasWallet
{
    public function getWallet(string $name, bool $lock = true)
    {
        return app(WalletServiceInterface::class)->findByName($name);
    }

    public function getWalletOrFail(string $name, bool $lock = true)
    {
        return app(WalletServiceInterface::class)->findByNameOrFail($name);
    }

    public function getWallets(string $name, bool $lock = true)
    {
        return app(WalletServiceInterface::class)->getAll();
    }

    public function hasWallet(string $name): bool
    {
        return (bool) app(WalletServiceInterface::class)->findByName($name);
    }

    public function createWallet(string $name, Model $model, bool $lockAfterCreate = true)
    {
        return app(WalletServiceInterface::class)->create($model, [
            'name' => $name,
        ]);
    }

    public function deleteWallet(string $name): bool
    {
        return (bool) app(WalletServiceInterface::class)->findByName($name)->delete();
    }
}
