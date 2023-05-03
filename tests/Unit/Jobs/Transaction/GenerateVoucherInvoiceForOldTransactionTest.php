<?php

namespace Tests\Unit\Jobs\Transaction;

use App\Actions\Contracts\Companies\ChargeLenderBalanceManually;
use App\Actions\Contracts\Wallets\CreateTransactions;
use App\Enums\MediaCollections\TransactionMediaCollection;
use App\Enums\TransactionReason;
use App\Enums\WalletType;
use App\Jobs\Transaction\GenerateVoucherInvoiceForOldTransaction;
use App\Models\Company;
use App\Models\Transaction;
use Cknow\Money\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;
use Tests\Traits\InteractsWithCompany;

class GenerateVoucherInvoiceForOldTransactionTest extends TestCase
{
    use RefreshDatabase, InteractsWithCompany;

    protected static Company $lender;

    protected function setUp(): void
    {
        parent::setUp();

        [self::$lender] = $this->createCompany();
    }

    public function test_generate_voucher_invoice_for_old_transaction_succeeded()
    {
        $data = [
            'amount' => 1000,
            'attachment' => UploadedFile::fake()
                ->create('attachment.pdf'),
            'description_en' => 'description en',
            'description_ar' => 'description ar',
        ];

        app(ChargeLenderBalanceManually::class)->handle(self::$lender, $data);

        (new GenerateVoucherInvoiceForOldTransaction)->handle();

        $transactions = Transaction::query()
            ->whereIn('reason', [
                TransactionReason::DepositByEdaat,
                TransactionReason::ManualDeposit,
            ])
            ->get();

        foreach ($transactions as $transaction) {
            $this->assertTrue($transaction->hasMedia(TransactionMediaCollection::VoucherInvoice));
        }
    }

    public function test_generate_voucher_invoice_for_old_transaction_only_works_with_deposit_by_edaat_and_manual_deposit()
    {
        $wallet = self::$lender->getWallet(WalletType::CompanyWallet);
        $amount = Money::parseByDecimal(10000, $wallet->currency);

        app(CreateTransactions::class)->handle($wallet, TransactionReason::OrderCreationFee, $amount, []);
        app(CreateTransactions::class)->handle($wallet, TransactionReason::VatPercentageFee, $amount, []);

        (new GenerateVoucherInvoiceForOldTransaction)->handle();

        $transactions = Transaction::query()
            ->whereNotIn('reason', [
                TransactionReason::DepositByEdaat,
                TransactionReason::ManualDeposit,
            ])
            ->get();

        foreach ($transactions as $transaction) {
            $this->assertFalse($transaction->hasMedia(TransactionMediaCollection::VoucherInvoice));
        }
    }
}
