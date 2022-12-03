<?php

namespace App\Support\Wallets;

use App\Models\Transaction;
use App\Models\Wallet;
use App\Support\Wallets\Contracts\WalletServiceInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class WalletService implements WalletServiceInterface
{
    /**
     * @param  Model  $model
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
                'uuid' => Str::uuid()->toString(),
            ]
        ));
    }

    /**
     * @param  int  $id
     * @param  bool  $lock
     * @return mixed
     */
    public function findById(int $id, bool $lock = true)
    {
        return $this->buildWalletQueryBase($lock)->find($id);
    }

    /**
     * @param  string  $uuid
     * @param  bool  $lock
     * @return mixed
     */
    public function findByUuid(string $uuid, bool $lock = true)
    {
        return $this->buildWalletQueryBase($lock)
            ->where('uuid', $uuid)
            ->first();
    }

    /**
     * @param  Model  $model
     * @param  string  $name
     * @param  bool  $lock
     * @return mixed
     */
    public function findByName(Model $model, string $name, bool $lock = true)
    {
        return $this->buildWalletQueryBase($lock)
            ->where('name', $name)
            ->where('holder_id', $model->getKey())
            ->where('holder_type', $model->getMorphClass())
            ->first();
    }

    /**
     * @param  int  $id
     * @param  bool  $lock
     * @return mixed
     */
    public function findByIdOrFail(int $id, bool $lock = true)
    {
        return $this->buildWalletQueryBase($lock)->findOrFail($id);
    }

    /**
     * @param  string  $uuid
     * @param  bool  $lock
     * @return mixed
     */
    public function findByUuidOrFail(string $uuid, bool $lock = true)
    {
        return $this->buildWalletQueryBase($lock)
            ->where('uuid', $uuid)
            ->firstOrFail();
    }

    /**
     * @param  Model  $model
     * @param  string  $name
     * @param  bool  $lock
     * @return mixed
     */
    public function findByNameOrFail(Model $model, string $name, bool $lock = true)
    {
        return $this->buildWalletQueryBase($lock)
            ->where('name', $name)
            ->where('holder_id', $model->getKey())
            ->where('holder_type', $model->getMorphClass())
            ->firstOrFail();
    }

    /**
     * @param  string|null  $name
     * @param  bool  $lock
     * @return mixed
     */
    public function getWallets(Model $model, string $name = null, bool $lock = true)
    {
        return $this->buildWalletQueryBase($lock)
            ->when($name, fn ($query) => $query->where('name', $name))
            ->where('holder_id', $model->getKey())
            ->where('holder_type', $model->getMorphClass())
            ->get();
    }

    /**
     * @param  Model  $model
     * @param  string  $name
     * @return mixed
     */
    public function hasWallet(Model $model, string $name)
    {
        return Wallet::where('holder_id', $model->getKey())
            ->where('holder_type', $model->getMorphClass())
            ->where('name', $name)
            ->exists();
    }

    /**
     * @param  Model  $model
     * @param  string  $name
     * @return mixed
     */
    public function transactions(Model $model, string $walletName): Builder
    {
        $wallet = $this->findByNameOrFail($model, $walletName);

        return Transaction::where('wallet_id', $wallet->getKey());
    }

    /**
     * @param  Model  $model
     * @param  string  $walletName
     * @return mixed
     */
    public function balance(Model $model, string $walletName)
    {
        $wallet = $this->findByNameOrFail($model, $walletName);

        return $wallet->balance;
    }

    /**
     * Build base of wallet query
     *
     * @param  bool  $lock
     * @return \Illuminate\Database\Query\Builder
     */
    protected function buildWalletQueryBase(bool $lock)
    {
        return Wallet::when($lock, fn ($query) => $query->lockForUpdate());
    }
}
