<?php

namespace Tests\Feature\Endpoints\Api\V1\Lender\Order;

use App\Enums\FinancingOrderProceedCase;
use App\Enums\Role;
use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;
use Tests\Traits\InteractsWithLender;

class MakeOrderProceedTest extends TestCase
{
    use RefreshDatabase, InteractsWithLender;

    private static Company $company;

    private static User $userLender;

    private static Builder|Model $financingOrder;

    /**
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        Artisan::call('module:seed');

        [self::$company] = $this->createCompany('2000', ['company_cr' => '1234567891']);
        self::$userLender = $this->createLenderUser(self::$company->id, Role::LenderAdmin, 'lenderAdmin@bim.com');
        self::$financingOrder = $this->createOrder(self::$company->id, self::$userLender->id);
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
}
