<?php

namespace Tests\Feature\Endpoints\Api\V1\Edaat;

use App\Enums\Role;
use App\Enums\WalletType;
use App\Models\Company;
use App\Models\EdaatInvoice;
use App\Models\User;
use App\Models\Wallet;
use Bavix\Wallet\Internal\Exceptions\ExceptionInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Grantify\Facades\Grantify;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class IndexInvoiceTest extends TestCase
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
            'amount' => 1,
            'status' => 1,
        ]);
    }

    public function testUnAuthUserCantIndexEdaatInvoicesWithValidData()
    {
        $this->withHeader('X-Company', self::$company->getOriginal('id'))
            ->getJson('api/v1/lender/edaat-invoices')
            ->assertStatus(Response::HTTP_UNAUTHORIZED)
            ->assertExactJson([
                'message' => 'Unauthenticated.',
            ]);
    }

    public function testAuthUserCanIndexEdaatInvoicesWithValidData()
    {
        $response = $this->actingAs(self::$userLender)
            ->withHeader('X-Company', self::$company->getOriginal('id'))
            ->getJson('api/v1/lender/edaat-invoices')
            ->assertStatus(Response::HTTP_OK)
            ->assertExactJson([
                'data' => [
                    [
                        'id' => 1,
                        'invoice_number' => '1',
                        'amount' => 1,
                        'amount_formatted' => '1.00',
                        'company_name' => 'Edaat',
                        'company_number' => 903,
                        'status' => [
                            'value' => 1,
                            'description' => 'Pending',
                        ],
                    ],
                ],
                'meta' => [
                    'pagination' => [
                        'total' => 1,
                        'count' => 1,
                        'per_page' => 15,
                        'current_page' => 1,
                        'total_pages' => 1,
                        'links' => [],
                    ],
                ],
            ]);
    }
}
