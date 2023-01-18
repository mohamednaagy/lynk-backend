<?php

namespace Tests\Feature\Endpoints\Api\V1\Edaat;

use App\Enums\Role;
use App\Models\Company;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use Tests\Traits\InteractsWithCompany;
use Tests\Traits\InteractsWithUser;

class WebhookTest extends TestCase
{
    use RefreshDatabase, InteractsWithUser, InteractsWithCompany;

    private static Company $company;

    private static User $userLender;

    private static Wallet $wallet;

    private static Builder|Model $edaatInvoice;

    /**
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        [self::$company, self::$wallet] = $this->createCompany('2000', ['company_cr' => '12345678910']);
        self::$userLender = $this->createLenderUser(self::$company->id, Role::LenderAdmin);
        self::$edaatInvoice = $this->createEdaatInvoice(self::$company->id, self::$userLender->id);
    }

    public function test_edaat_invoices_webhook_with_paid_invoice_success()
    {
        $transactionsCount = DB::connection(Config::get('wallet.database.connection'))
            ->table('transactions')->count();
        $this->withHeader('X-Company', self::$company->id)
            ->postJson('api/edaat/webhook/payment', [
                [
                    'InvoiceNo' => '90510539184806',
                    'BillNo' => '9051053918479001',
                    'InternalCode' => self::$edaatInvoice->id,
                    'PaymentAmount' => 3000,
                    'PaymentDate' => '2022-11-15T19:35:15.2960584+03:00',
                    'ProductIds' => [10559],
                    'EPTN' => '638041377152960584',
                ],
            ])->assertOk();

        $transactions = DB::connection(Config::get('wallet.database.connection'))
            ->table('transactions')
            ->where('meta->invoice_number', self::$edaatInvoice->invoice_number)
            ->first();

        $this->assertEquals(self::$edaatInvoice->amount->getAmount(), $transactions->amount);
        $this->assertDatabaseCount(Transaction::class, $transactionsCount + 1);
    }

    public function test_edaat_invoices_webhook_with_un_paid_invoice_fail()
    {
        $transactionsCount = Transaction::query()->count();
        $this->withHeader('X-Company', self::$company->id)
            ->postJson('api/edaat/webhook/payment', [
                [
                    'InvoiceNo' => '1',
                    'BillNo' => '1',
                    'InternalCode' => self::$edaatInvoice->id,
                    'PaymentAmount' => 2,
                    'PaymentDate' => '2022-11-15T19:35:15.2960584+03:00',
                    'ProductIds' => [10559],
                    'EPTN' => '1',
                ],
            ])->assertOk();

        $this->assertDatabaseCount(Transaction::class, $transactionsCount);
    }
}
