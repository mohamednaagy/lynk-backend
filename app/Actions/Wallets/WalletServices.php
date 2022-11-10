<?php

namespace App\Actions\Wallets;

use App\Actions\Contracts\Wallets\WalletService;
use App\Models\Wallet;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class WalletServiceAction implements WalletService
{
    /**
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @param  array  $data
     * @return mixed
     */
    public function create(Model $model, array $data): Wallet
    {
        return Wallet::create(array_merge(
            $data,
            [
                'holder_type' => $model->getMorphClass(),
                'holder_id' => $model->getKey(),
                'uuid' => Str::uuid(),
            ]
        ));
    }

    /**
     * @param  int  $id
     * @return mixed
     */
    public function findById(int $id)
    {
        return Wallet::find($id);
    }

    /**
     * @param  string  $uuid
     * @return mixed
     */
    public function findByUuid(string $uuid)
    {
        return Wallet::where('uuid', $uuid)->first();
    }

    /**
     * @param  string  $name
     * @return mixed
     */
    public function findByName(string $name)
    {
        return Wallet::where('name', $name)->first();
    }

    /**
     * @param  int  $id
     * @return mixed
     */
    public function findByIdOrFail(int $id)
    {
        return Wallet::findOrFail($id);
    }

    /**
     * @param  string  $uuid
     * @return mixed
     */
    public function findByUuidOrFail(string $uuid)
    {
        return Wallet::where('uuid', $uuid)->firstOrFail();
    }

    /**
     * @param  string  $name
     * @return mixed
     */
    public function findByNameOrFail(string $name)
    {
        return Wallet::where('name', $name)->firstOrFail();
    }
}
