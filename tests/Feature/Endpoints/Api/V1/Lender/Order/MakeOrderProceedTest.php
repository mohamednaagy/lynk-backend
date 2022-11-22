<?php

namespace Tests\Feature\Endpoints\Api\V1\Lender\Order;

use App\Enums\FinancingOrderProceedCase;
use App\Enums\FinancingOrderStatus;
use App\Enums\Role;
use App\Models\Company;
use App\Models\FinancingOrder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Modules\Grantify\Facades\Grantify;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class MakeOrderProceedTest extends TestCase
{
    use RefreshDatabase;

    private static Company $company;

    private static User $userLender;

    private static FinancingOrder $financingOrder;

    /**
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        Artisan::call('module:seed');

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

        self::$userLender = User::factory()->create([
            'email' => 'lender@bim.com',
            'password' => bcrypt('12345678'),
            'company_id' => self::$company->getOriginal('id'),
            'email_verified_at' => now(),
        ]);

        Grantify::assignRoleToModel(self::$userLender, Role::LenderAdmin);

        self::$financingOrder = $this->createFinancingOrder();
    }

    /**
     * @return void
     */
    public function test_that_unAuth_user_cant_make_order_proceed(): void
    {
        $this->withHeader('X-Company', self::$company->getOriginal('id'))
            ->postJson('api/v1/lender/orders/'.self::$financingOrder->getOriginal('id').'/proceed')
            ->assertStatus(Response::HTTP_UNAUTHORIZED)
            ->assertExactJson([
                'message' => 'Unauthenticated.',
            ]);
    }

    /**
     * @return void
     */
    public function test_make_order_proceed_on_empty_case(): void
    {
        $response = $this->actingAs(self::$userLender)
            ->withHeader('X-Company', self::$company->getOriginal('id'))
            ->postJson('api/v1/lender/orders/'.self::$financingOrder->getOriginal('id').'/proceed', [
                'case' => '',
            ]);

        $response->assertStatus(422)->assertExactJson(
            [
                'message' => 'The case field is required.',
                'errors' => [
                    'case' => [
                        'The case field is required.',
                    ],
                ],
            ]
        );
    }

    /**
     * @return void
     */
    public function test_make_order_proceed_on_invalid_case(): void
    {
        $response = $this->actingAs(self::$userLender)
            ->withHeader('X-Company', self::$company->getOriginal('id'))
            ->postJson('api/v1/lender/orders/'.self::$financingOrder->getOriginal('id').'/proceed', [
                'case' => 'TEST_PROCEED_CASE',
            ]);

        $response->assertStatus(422)->assertExactJson(
            [
                'message' => 'The value you have entered is invalid.',
                'errors' => [
                    'case' => [
                        'The value you have entered is invalid.',
                    ],
                ],
            ]
        );
    }

    /**
     * @return void
     */
    public function test_make_order_proceed_on_order_status_doesnt_follow_sequence(): void
    {
        $response = $this->actingAs(self::$userLender)
            ->withHeader('X-Company', self::$company->getOriginal('id'))
            ->postJson('api/v1/lender/orders/'.self::$financingOrder->getOriginal('id').'/proceed', [
                'case' => FinancingOrderProceedCase::ContractSigned,
            ]);

        $response->assertStatus(400)->assertJsonStructure([
            'message',
            'code',
        ]);
    }

    private function createFinancingOrder(): FinancingOrder
    {
        return FinancingOrder::factory()->create([
            'company_id' => self::$company->getOriginal('id'),
            'national_id' => '2553451234',
            'phone_number' => '+966503811000',
            'amount' => 1000,
            'selling_price' => 1000.5,
            'status' => FinancingOrderStatus::PendingApproval,
            'creator_id' => '2',
            'creator_type' => 'App\Models\User',
            'is_verification_required' => 0,
        ]);
    }
}
