<?php

namespace App\Support\Wallets\Traits;

use App\Support\Wallets\Contracts\WalletServiceInterface;

trait HasWallet
{
    public function getWallet(string $name, bool $lock = true)
    {
        return app(WalletServiceInterface::class)->findByName($this, $name, $lock);
    }

    public function getWalletOrFail(string $name, bool $lock = true)
    {
        return app(WalletServiceInterface::class)->findByNameOrFail($this, $name, $lock);
    }

    public function getWallets(?string $name, bool $lock = true)
    {
        /** @var \Illuminate\Database\Eloquent\Model $this */
        return app(WalletServiceInterface::class)->getWallets(model: $this, name: $name, lock: $lock);
    }

    public function hasWallet(string $name): bool
    {
        /** @var \Illuminate\Database\Eloquent\Model $this */
        return (bool) app(WalletServiceInterface::class)->hasWallet($this, $name);
    }

    public function createWallet(string $name)
    {
        return app(WalletServiceInterface::class)->create($this, [
            'name' => $name,
        ]);
    }
}
