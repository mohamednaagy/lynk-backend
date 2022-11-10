<?php

namespace App\Actions\Contracts\Wallets;

use App\Models\Wallet;
use Illuminate\Database\Eloquent\Model;

interface WalletService
{
    public function create(Model $model, array $data): Wallet;

    public function findById(int $id);

    public function findByUuid(string $uuid);

    public function findByName(string $name);

    public function findByIdOrFail(int $id);

    public function findByUuidOrFail(string $uuid);

    public function findByNameOrFail(string $name);
}
