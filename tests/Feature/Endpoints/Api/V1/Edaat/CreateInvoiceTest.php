<?php

namespace Tests\Feature\Endpoints\Api\V1\Edaat;

use App\Enums\Role;
use App\Enums\WalletType;
use App\Models\Company;
use App\Models\User;
use Bavix\Wallet\Internal\Exceptions\ExceptionInterface;
use Bavix\Wallet\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Grantify\Facades\Grantify;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class CreateInvoiceTest extends TestCase
{
    use RefreshDatabase;

    private static Company $company;

    private static User $userLender;

    private static Wallet $wallet;

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
    }

    public function testUnAuthUserCantCreateEdaatInvoiceWithValidData()
    {
        $this->withHeader('X-Company', self::$company->getOriginal('id'))
            ->postJson('api/v1/lender/edaat-invoices')
            ->assertStatus(Response::HTTP_UNAUTHORIZED)
            ->assertExactJson([
                'message' => 'Unauthenticated.',
            ]);
    }

    public function testAuthUserCantCreateEdaatInvoiceWithoutOrderCount()
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

    public function testAuthUserCanCreateEdaatInvoiceWithValidData()
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
