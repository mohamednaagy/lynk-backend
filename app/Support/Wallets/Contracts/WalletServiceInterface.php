<?php

namespace App\Support\Wallets\Contracts;

use App\Models\Wallet;
use Illuminate\Database\Eloquent\Model;

interface WalletServiceInterface
{
    public function create(Model $model, array $data): Wallet;

    public function findById(int $id, bool $lock);

    public function findByUuid(string $uuid, bool $lock);

    public function findByName(Model $model, string $name, bool $lock);

    public function findByIdOrFail(int $id, bool $lock);

    public function findByUuidOrFail(string $uuid, bool $lock);

    public function findByNameOrFail(Model $model, string $name, bool $lock);

    public function getWallets(Model $model, ?string $name, bool $lock);

    public function hasWallet(Model $model, string $name);
}
