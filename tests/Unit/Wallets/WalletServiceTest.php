<?php

namespace Tests\Unit\Wallets;

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
use Tests\Traits\InteractsWithCompany;

class WalletServiceTest extends TestCase
{
    use RefreshDatabase, InteractsWithCompany;

    private static WalletService $walletService;

    private static Company $company;

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
        [self::$company, self::$wallet] = $this->createCompany(2000);
    }

    public function test_wallet_service_create_method_return_wallet_model()
    {
        $wallet = self::$walletService->create(self::$company, self::$walletInformation);
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
        $wallet = self::$walletService->findByName(self::$company, self::$wallet->name);
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
        $wallet = self::$walletService->findByNameOrFail(self::$company, self::$wallet->name);
        $this->assertInstanceOf(Wallet::class, $wallet);
        $this->assertSame($wallet->name, self::$wallet->name);
    }

    public function test_wallet_service_find_by_id_method_return_null_when_model_not_found()
    {
        $wallet = self::$walletService->findById(8000000);
        $this->assertNull($wallet);
    }

    public function test_wallet_service_find_by_uuid_method_return_null_when_model_not_found()
    {
        $wallet = self::$walletService->findByUuid(404);
        $this->assertNull($wallet);
    }

    public function test_wallet_service_find_by_name_method_return_null_when_model_not_found()
    {
        $wallet = self::$walletService->findByName(self::$company, 'not exists name');
        $this->assertNull($wallet);
    }

    public function test_wallet_service_find_by_name_or_fail_method_throw_exception_if_model_not_found()
    {
        $this->expectException(ModelNotFoundException::class);
        self::$walletService->findByNameOrFail(self::$company, 'not exists name');
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
        $this->assertInstanceOf(Collection::class, self::$walletService->getWallets(self::$company));
    }

    public function test_wallet_service_get_wallets_method_return_his_wallets_collection()
    {
        $this->assertEquals(1, self::$walletService->getWallets(self::$company)->count());
    }

    public function test_wallet_service_has_wallet_method_return_bool()
    {
        $transactions = self::$walletService->hasWallet(self::$company, WalletType::CompanyWallet);

        $this->assertIsBool($transactions);
    }

    public function test_wallet_service_transactions_method_return_query_builder()
    {
        $transactions = self::$walletService->transactions(self::$company, WalletType::CompanyWallet);

        $this->assertInstanceOf(Builder::class, $transactions);
    }

    public function test_wallet_service_transactions_method_return_query_builder_of_the_model()
    {
        Transaction::factory(2)->create(['wallet_id' => self::$wallet->id]);

        $this->assertEquals(
            3,
            self::$walletService->transactions(self::$company, self::$wallet->name)->count()
        );
    }

    public function test_wallet_service_balance_method_return_wallet_balance()
    {
        $balance = self::$walletService->balance(self::$company, WalletType::CompanyWallet);
        $this->assertEquals(2000, $balance->getAmount());
    }

    public function test_wallet_service_balance_method_throw_exception_if_wallet_not_found()
    {
        $this->expectException(ModelNotFoundException::class);
        self::$walletService->balance(self::$company, 'not exists wallet');
    }
}
