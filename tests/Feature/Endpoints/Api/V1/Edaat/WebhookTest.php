<?php

namespace Tests\Feature\Endpoints\Api\V1\Edaat;

use App\Enums\Role;
use App\Models\Company;
use App\Models\User;
use Bavix\Wallet\Models\Transaction;
use Bavix\Wallet\Models\Wallet;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;
use Tests\Traits\InteractsWithLender;

class WebhookTest extends TestCase
{
    use RefreshDatabase, InteractsWithLender;

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
        self::$userLender = $this->createLenderUser(self::$company->id, Role::LenderAdmin, 'lenderAdmin@bim.com');
        self::$edaatInvoice = $this->createEdaatInvoice(self::$company->id, self::$userLender->id);
    }

    public function test_edaat_invoices_webhook_with_paid_invoice_success()
    {
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
            ])->assertStatus(Response::HTTP_OK);
        $transactions = Transaction::all();

        $this->assertEquals(self::$edaatInvoice->amount, $transactions->last()->amount);
        $this->assertEquals('deposit', $transactions->last()->type);
        $this->assertDatabaseCount(Transaction::class, 2);
    }

    public function testEdaatInvoicesWebhookWithUnPaidInvoiceFail()
    {
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
            ])->assertStatus(Response::HTTP_OK);
        $this->assertDatabaseCount(Transaction::class, 1);
    }
}
