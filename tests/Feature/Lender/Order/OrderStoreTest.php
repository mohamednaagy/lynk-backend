<?php

namespace Tests\Feature\Lender\Order;

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

class OrderStoreTest extends TestCase
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

    /**
     * @return void
     */
    public function testThatUnAuthUserCantCreateOrder(): void
    {
        $this->withHeader('X-Company', self::$company->getOriginal('id'))
            ->postJson('api/v1/lender/orders', [
                'national_id' => '2553451234',
                'amount' => '200',
                'selling_price' => '220',
                'phone_country_code' => 'SA',
                'phone_number' => '500112233',
            ])->assertStatus(Response::HTTP_UNAUTHORIZED)
            ->assertExactJson([
                'message' => 'Unauthenticated.',
            ]);
    }

    /**
     * @return void
     */
    public function testThatAuthUserWithoutNationalIdCantCreateOrder(): void
    {
        $this->actingAs(self::$userLender)->withHeader('X-Company', self::$company->getOriginal('id'))
            ->postJson('api/v1/lender/orders', [
                'amount' => '200',
                'selling_price' => '220',
                'phone_country_code' => 'SA',
                'phone_number' => '500112233',
            ])->assertStatus(Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    /**
     * @return void
     */
    public function testThatAuthUserWithoutAmountCantCreateOrder(): void
    {
        $this->actingAs(self::$userLender)->withHeader('X-Company', self::$company->getOriginal('id'))
            ->postJson('api/v1/lender/orders', [
                'national_id' => '2553451234',
                'selling_price' => '220',
                'phone_country_code' => 'SA',
                'phone_number' => '500112233',
            ])->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertExactJson([
                'message' => 'The amount field is required.',
                'errors' => [
                    'amount' => [
                        0 => 'The amount field is required.',
                    ],
                ],
            ]);
    }

    /**
     * @return void
     */
    public function testThatAuthUserWithoutSellingPriceCantCreateOrder(): void
    {
        $this->actingAs(self::$userLender)->withHeader('X-Company', self::$company->getOriginal('id'))
            ->postJson('api/v1/lender/orders', [
                'national_id' => '2553451234',
                'amount' => '200',
                'phone_country_code' => 'SA',
                'phone_number' => '500112233',
            ])->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertExactJson([
                'message' => 'The selling price field is required.',
                'errors' => [
                    'selling_price' => [
                        0 => 'The selling price field is required.',
                    ],
                ],
            ]);
    }

    /**
     * @return void
     */
    public function testThatAuthUserWithoutPhoneCountryCodeCantCreateOrder(): void
    {
        $this->actingAs(self::$userLender)->withHeader('X-Company', self::$company->getOriginal('id'))
            ->postJson('api/v1/lender/orders', [
                'national_id' => '2553451234',
                'amount' => '200',
                'selling_price' => '220',
                'phone_number' => '500112233',
            ])->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertExactJson([
                'message' => 'The phone country code field is required when phone number is present. (and 1 more error)',
                'errors' => [
                    'phone_country_code' => [
                        'The phone country code field is required when phone number is present.',
                    ],
                    'phone_number' => [
                        'The phone number is not valid phone number.',
                    ],
                ],
            ]);
    }

    /**
     * @return void
     */
    public function testThatAuthUserWithoutPhoneNumberCantCreateOrder(): void
    {
        $this->actingAs(self::$userLender)->withHeader('X-Company', self::$company->getOriginal('id'))
            ->postJson('api/v1/lender/orders', [
                'national_id' => '2553451234',
                'amount' => '200',
                'selling_price' => '220',
                'phone_country_code' => 'SA',
            ])->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertExactJson([
                'message' => 'The phone number field is required. (and 1 more error)',
                'errors' => [
                    'phone_number' => [
                        'The phone number field is required.',
                    ],
                    'national_id' => [
                        "Phone number does't belong to national ID/Iqama",
                    ],
                ],
            ]);
    }

    /**
     * @return void
     */
    public function testThatAuthUserCanCreateOrderWithValidData(): void
    {
        $this->actingAs(self::$userLender)
            ->withHeader('X-Company', self::$company->getOriginal('id'))
            ->postJson('api/v1/lender/orders', [
                'national_id' => '2553451234',
                'amount' => '200',
                'selling_price' => '220',
                'phone_country_code' => 'SA',
                'phone_number' => '500112233',
            ])->assertStatus(Response::HTTP_OK)
            ->assertJsonStructure([
                'data' => [
                    'phone_country_code',
                    'phone_number',
                    'phone_number_formatted',
                    'id',
                    'status' => [
                        'description',
                        'value',
                    ],
                    'company_id',
                    'reference_number',
                    'national_id',
                    'amount',
                    'selling_price',
                    'contract',
                    'power_of_attorney',
                    'is_approved',
                    'status_reason',
                ],
            ]);
    }
}
