<?php

namespace App\Support\Wallets\Traits;

use App\Models\Wallet;
use App\Support\Wallets\Contracts\WalletServiceInterface;
use Illuminate\Database\Eloquent\Model;

trait HasWallet
{
    public function getWallet(string $name, bool $lock = true): Wallet
    {
        /** @var Model $this */
        return app(WalletServiceInterface::class)->findByName($this, $name, $lock);
    }

    public function getWalletOrFail(string $name, bool $lock = true)
    {
        /** @var Model $this */
        return app(WalletServiceInterface::class)->findByNameOrFail($this, $name, $lock);
    }

    public function getWallets(string $name = null, bool $lock = true)
    {
        /** @var Model $this */
        return app(WalletServiceInterface::class)->getWallets(model: $this, name: $name, lock: $lock);
    }

    public function hasWallet(string $name): bool
    {
        /** @var Model $this */
        return (bool) app(WalletServiceInterface::class)->hasWallet($this, $name);
    }

    public function createWallet(string $name, string $currency = null)
    {
        /** @var Model $this */
        return app(WalletServiceInterface::class)->create($this, [
            'name' => $name,
            'currency' => $currency,
        ]);
    }

    public function transactions(string $name)
    {
        /** @var Model $this */
        return app(WalletServiceInterface::class)->transactions($this, $name);
    }
}
