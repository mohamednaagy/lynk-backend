<?php

namespace Tests\Unit\Wallet;

use App\Enums\WalletType;
use App\Models\Company;
use App\Models\Transaction;
use App\Models\Wallet;
use App\Support\Wallets\WalletService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\InteractsWithLender;

class WalletServiceTest extends TestCase
{
    use RefreshDatabase;
    use InteractsWithLender;

    private static WalletService $walletService;

    private static Company $model;

    private static wallet $wallet;

    private static array $walletInformation;

    public function setUp(): void
    {
        parent::setUp();

        self::$walletInformation = [
            'name' => 'wallet name',
            'currency' => 'SAR',
        ];
        self::$walletService = new WalletService();
        [self::$model, self::$wallet] = $this->createCompany(2000);
    }

    public function test_wallet_service_create_method_return_wallet_model()
    {
        $wallet = self::$walletService->create(self::$model, self::$walletInformation);
        $this->assertInstanceOf(Wallet::class, $wallet);
        $this->assertSame($wallet->name, self::$walletInformation['name']);
    }

    public function test_wallet_service_find_by_id_method_return_wallet_model()
    {
        $wallet = self::$walletService->findById(self::$wallet->id);
        $this->assertInstanceOf(Wallet::class, $wallet);
        $this->assertSame($wallet->id, self::$wallet->id);
    }

    public function test_wallet_service_find_by_uuid_method_return_wallet_model()
    {
        $wallet = self::$walletService->findByUuid(self::$wallet->uuid);
        $this->assertInstanceOf(Wallet::class, $wallet);
        $this->assertSame($wallet->uuid, self::$wallet->uuid);
    }

    public function test_wallet_service_find_by_name_method_return_wallet_model()
    {
        $wallet = self::$walletService->findByName(self::$model, self::$wallet->name);
        $this->assertInstanceOf(Wallet::class, $wallet);
        $this->assertSame($wallet->name, self::$wallet->name);
    }

    public function test_wallet_service_find_by_id_or_fail_method_return_wallet_model()
    {
        $wallet = self::$walletService->findByIdOrFail(self::$wallet->id);
        $this->assertInstanceOf(Wallet::class, $wallet);
        $this->assertSame($wallet->id, self::$wallet->id);
    }

    public function test_wallet_service_find_by_uuid_or_fail_method_return_wallet_model()
    {
        $wallet = self::$walletService->findByUuidOrFail(self::$wallet->uuid);
        $this->assertInstanceOf(Wallet::class, $wallet);
        $this->assertSame($wallet->uuid, self::$wallet->uuid);
    }

    public function test_wallet_service_find_by_name_or_fail_method_return_wallet_model()
    {
        $wallet = self::$walletService->findByNameOrFail(self::$model, self::$wallet->name);
        $this->assertInstanceOf(Wallet::class, $wallet);
        $this->assertSame($wallet->name, self::$wallet->name);
    }

    public function test_wallet_service_find_by_id_method_return_null_when_model_not_found()
    {
        $wallet = self::$walletService->findById(404);
        $this->assertEmpty($wallet);
    }

    public function test_wallet_service_find_by_uuid_method_return_null_when_model_not_found()
    {
        $wallet = self::$walletService->findByUuid(404);
        $this->assertEmpty($wallet);
    }

    public function test_wallet_service_find_by_name_method_return_null_when_model_not_found()
    {
        $wallet = self::$walletService->findByName(self::$model, 'not exists name');
        $this->assertEmpty($wallet);
    }

    public function test_wallet_service_find_by_name_or_fail_method_throw_exception_if_model_not_found()
    {
        $this->expectException(ModelNotFoundException::class);
        self::$walletService->findByNameOrFail(self::$model, 'not exiosts name');
    }

    public function test_wallet_service_find_by_uuid_or_fail_method_throw_exception_if_model_not_found()
    {
        $this->expectException(ModelNotFoundException::class);
        self::$walletService->findByUuidOrFail(404);
    }

    public function test_wallet_service_find_by_id_or_fail_method_throw_exception_if_model_not_found()
    {
        $this->expectException(ModelNotFoundException::class);
        self::$walletService->findByIdOrFail(404);
    }

    public function test_wallet_service_get_wallets_method_return_collection()
    {
        $this->assertInstanceOf(Collection::class, self::$walletService->getWallets(self::$model));
    }

    public function test_wallet_service_get_wallets_method_return_his_wallets_collection()
    {
        $this->assertEquals(1, self::$walletService->getWallets(self::$model)->count());
    }

    public function test_wallet_service_has_wallet_method_return_bool()
    {
        $transactions = self::$walletService->hasWallet(self::$model, WalletType::CompanyWallet);

        $this->assertIsBool($transactions);
    }

    public function test_wallet_service_transactions_method_return_query_builder()
    {
        $transactions = self::$walletService->transactions(self::$model, WalletType::CompanyWallet);

        $this->assertInstanceOf(Builder::class, $transactions);
    }

    public function test_wallet_service_transactions_method_return_query_builder_of_the_model()
    {
        Transaction::factory(2)->create(['wallet_id' => self::$wallet->id]);

        $this->assertEquals(
            3,
            self::$walletService->transactions(self::$model, self::$wallet->name)->count()
        );
    }

    public function test_wallet_service_balance_method_return_wallet_balance()
    {
        $balance = self::$walletService->balance(self::$model, WalletType::CompanyWallet);
        $this->assertEquals(2000, $balance->getAmount());
    }

    public function test_wallet_service_balance_method_throw_exception_if_wallet_not_found()
    {
        $this->expectException(ModelNotFoundException::class);
        self::$walletService->balance(self::$model, 'not exists wallet');
    }
}
