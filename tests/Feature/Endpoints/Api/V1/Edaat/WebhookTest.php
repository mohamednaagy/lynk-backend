<?php

namespace Tests\Feature\Endpoints\Api\V1\Edaat;

use App\Enums\Role;
use App\Enums\WalletType;
use App\Models\Company;
use App\Models\EdaatInvoice;
use App\Models\User;
use Bavix\Wallet\Internal\Exceptions\ExceptionInterface;
use Bavix\Wallet\Models\Transaction;
use Bavix\Wallet\Models\Wallet;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Grantify\Facades\Grantify;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class WebhookTest extends TestCase
{
    use RefreshDatabase;

    private static Company $company;

    private static User $userLender;

    private static Wallet $wallet;

    private static Builder|Model $edaatInvoice;

    /**
     * @return void
     *
     * @throws ExceptionInterface
     */
    public function setUp(): void
    {
        parent::setUp();

        self::$company = Company::factory()->create([
            'first_name' => 'firstName',
            'last_name' => 'lastName',
            'phone_country_code' => 'SA',
            'phone_number' => '503811000',
            'email' => 'test@uselynk.test',
            'password' => 'Qwer@1234',
            'source' => 'Postman',
            'company_name' => 'companyName',
            'company_unique_name' => 'lynk05',
            'company_cr' => '12345678910',
        ]);

        self::$wallet = self::$company->createWallet([
            'name' => WalletType::CompanyWallet,
            'slug' => WalletType::CompanyWallet,
        ]);

        self::$wallet->deposit(2000);

        self::$userLender = User::factory()->create([
            'email' => 'lender@bim.com',
            'password' => bcrypt('12345678'),
            'company_id' => self::$company->getOriginal('id'),
        ]);

        Grantify::assignRoleToModel(self::$userLender, Role::LenderAdmin);

        self::$edaatInvoice = EdaatInvoice::query()->create([
            'company_id' => self::$company->getOriginal('id'),
            'creator_id' => self::$userLender->getOriginal('id'),
            'invoice_number' => 1,
            'amount' => 100,
            'status' => 1,
        ]);
    }

    public function testEdaatInvoicesWebhookWithPaidInvoiceSuccess()
    {
        $this->withHeader('X-Company', self::$company->getOriginal('id'))
            ->postJson('api/edaat/webhook/payment', [
                [
                    'InvoiceNo' => '90510539184806',
                    'BillNo' => '9051053918479001',
                    'InternalCode' => self::$edaatInvoice->getOriginal('id'),
                    'PaymentAmount' => 3000,
                    'PaymentDate' => '2022-11-15T19:35:15.2960584+03:00',
                    'ProductIds' => [10559],
                    'EPTN' => '638041377152960584',
                ],
            ])->assertStatus(Response::HTTP_OK);
        $transactions = Transaction::all();

        $this->assertEquals(self::$edaatInvoice->getOriginal('amount'), $transactions->last()->amount);
        $this->assertEquals('deposit', $transactions->last()->type);
        $this->assertDatabaseCount(Transaction::class, 2);
    }

    public function testEdaatInvoicesWebhookWithUnPaidInvoiceFail()
    {
        $this->withHeader('X-Company', self::$company->getOriginal('id'))
            ->postJson('api/edaat/webhook/payment', [
                [
                    'InvoiceNo' => '1',
                    'BillNo' => '1',
                    'InternalCode' => self::$edaatInvoice->getOriginal('id'),
                    'PaymentAmount' => 2,
                    'PaymentDate' => '2022-11-15T19:35:15.2960584+03:00',
                    'ProductIds' => [10559],
                    'EPTN' => '1',
                ],
            ])->assertStatus(Response::HTTP_OK);
        $this->assertDatabaseCount(Transaction::class, 1);
    }
}
