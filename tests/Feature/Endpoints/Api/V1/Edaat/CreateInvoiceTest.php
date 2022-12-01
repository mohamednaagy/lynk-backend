<?php

namespace Tests\Feature\Endpoints\Api\V1\Edaat;

use App\Enums\Role;
use App\Models\Company;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;
use Tests\Traits\InteractsWithLender;

class CreateInvoiceTest extends TestCase
{
    use RefreshDatabase, InteractsWithLender;

    private static Company $company;

    private static User $userLender;

    private static Wallet $wallet;

    /**
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        [self::$company, self::$wallet] = $this->createCompany('2000', ['company_cr' => '12345678910']);
        self::$userLender = $this->createLenderUser(self::$company->id, Role::LenderAdmin, 'lenderAdmin@bim.com');
    }

    public function test_un_auth_user_cant_create_edaat_invoice_with_valid_data()
    {
        $this->withHeader('X-Company', self::$company->getOriginal('id'))
            ->postJson('api/v1/lender/edaat-invoices')
            ->assertStatus(Response::HTTP_UNAUTHORIZED)
            ->assertExactJson([
                'message' => 'Unauthenticated.',
            ]);
    }

    public function test_auth_user_cant_create_edaat_invoice_without_order_count()
    {
        $this->actingAs(self::$userLender)
            ->withHeader('X-Company', self::$company->getOriginal('id'))
            ->postJson('api/v1/lender/edaat-invoices')
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertExactJson([
                'message' => 'The orders count field is required.',
                'errors' => [
                    'orders_count' => [
                        'The orders count field is required.',
                    ],
                ],
            ]);
    }

    public function test_auth_user_can_create_edaat_invoice_with_valid_data()
    {
        $this->actingAs(self::$userLender)
            ->withHeader('X-Company', self::$company->getOriginal('id'))
            ->postJson('api/v1/lender/edaat-invoices', [
                'orders_count' => 1,
            ])->assertStatus(Response::HTTP_OK)
            ->assertJsonStructure([
                'data' => [
                    'invoice_number',
                    'amount',
                    'company_name',
                    'company_number',
                ],
            ]);
    }
}
