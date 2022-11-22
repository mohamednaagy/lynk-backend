<?php

namespace Tests\Feature\Lender\Order;

use App\Enums\Role;
use App\Enums\WalletType;
use App\Models\Company;
use App\Models\FinancingOrder;
use App\Models\User;
use Bavix\Wallet\Internal\Exceptions\ExceptionInterface;
use Bavix\Wallet\Models\Wallet;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Grantify\Facades\Grantify;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class OrderUpdateTest extends TestCase
{
    use RefreshDatabase;

    private static Company $company;

    private static User $userLender;

    private static Wallet $wallet;

    private static Builder|Model $order;

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

        self::$order = FinancingOrder::query()->create([
            'company_id' => self::$company->getOriginal('id'),
            'approved_at' => Carbon::now(),
            'creator_id' => self::$userLender->getOriginal('id'),
            'creator_type' => User::class,
            'national_id' => '2553451234',
            'phone_number' => '+966500112233',
            'amount' => 200,
            'selling_price' => 220,
            'status' => 11,
            'is_verification_required' => 1,
        ]);
    }

    /**
     * @return void
     */
    public function testThatUnAuthUserCantUpdateOrder(): void
    {
        $this->withHeader('X-Company', self::$company->getOriginal('id'))
            ->putJson('api/v1/lender/orders/'.self::$order->getOriginal('id'))
            ->assertStatus(Response::HTTP_UNAUTHORIZED)
            ->assertExactJson([
                'message' => 'Unauthenticated.',
            ]);
    }

    /**
     * @return void
     */
    public function testThatAuthUserCanUpdateOrder(): void
    {
        $this->actingAs(self::$userLender)
            ->withHeader('X-Company', self::$company->getOriginal('id'))
            ->putJson('api/v1/lender/orders/'.self::$order->getOriginal('id'), [
                'national_id' => '2553451234',
                'amount' => '200',
                'selling_price' => '300',
                'phone_country_code' => 'SA',
                'phone_number' => '500112233',
            ])->assertStatus(Response::HTTP_OK)
            ->assertExactJson([
                'data' => [
                    'phone_country_code' => 'SA',
                    'phone_number' => '0500112233',
                    'phone_number_formatted' => '+966 50 011 2233',
                    'id' => 1,
                    'status' => [
                        'description' => 'Waiting client wakala',
                        'value' => 11,
                    ],
                    'company_id' => 1,
                    'reference_number' => null,
                    'national_id' => '2553451234',
                    'amount' => '200',
                    'selling_price' => '300',
                    'contract' => '',
                    'power_of_attorney' => '',
                    'is_approved' => true,
                    'status_reason' => null,
                ],
            ]);
    }
}
