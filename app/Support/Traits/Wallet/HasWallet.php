<?php

namespace App\Support\Traits\Wallet;

trait HasWallet
{
    public function getWallet(string $name, bool $lock = true)
    {
        return 'wallet or null';
    }

    public function getWalletOrFail(string $name, bool $lock = true)
    {
        return 'wallet or throw App\Exceptions\Wallet\WalletNotFoundException';
    }

    public function getWallets(string $name, bool $lock = true)
    {
        return 'collection of wallets';
    }

    public function hasWallet(string $name)
    {
        return 'boolean';
    }

    public function createWallet(string $name, bool $lockAfterCreate = true)
    {
        return 'Wallet model';
    }

    public function deleteWallet(string $name)
    {
        return 'boolean';
    }
}
