<?php

namespace Tests\Feature\Endpoints\Api\V1\Lender\FinancingOrders;

use App\Enums\Role;
use App\Models\Company;
use App\Models\User;
use App\Models\Wallet;
use App\Transformers\FinancingOrderTransformer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;
use Tests\Traits\InteractsWithCompany;
use Tests\Traits\InteractsWithUser;

class FinancingOrderControllerShowTest extends TestCase
{
    use RefreshDatabase, InteractsWithUser, InteractsWithCompany;

    private static Company $firstCompany;

    private static Company $secondCompany;

    private static User $userLenderAdmin;

    private static User $userLenderSupervisor;

    private static User $userLenderBilling;

    private static User $userLenderOrderCreator;

    private static Wallet $firstWallet;

    private static Wallet $secondWallet;

    private static Builder|Model $firstOrderInSameCompany;

    private static Builder|Model $secondOrderInSameCompany;

    private static Builder|Model $thirdOrderInSameCompany;

    private static Builder|Model $firstOrderInOtherCompany;

    public function setUp(): void
    {
        parent::setUp();

        [self::$firstCompany, self::$firstWallet] = $this->createCompany('2000', ['company_cr' => '12345678910']);
        [self::$secondCompany, self::$secondWallet] = $this->createCompany('3000', ['company_cr' => '12345678911']);
        self::$userLenderAdmin = $this->createLenderUser(self::$firstCompany->id, Role::LenderAdmin);
        self::$userLenderSupervisor = $this->createLenderUser(self::$firstCompany->id, Role::LenderSupervisor);
        self::$userLenderBilling = $this->createLenderUser(self::$firstCompany->id, Role::LenderBilling);
        self::$userLenderOrderCreator = $this->createLenderUser(self::$firstCompany->id, Role::LenderOrderCreator);
        self::$firstOrderInSameCompany = $this->createOrder(self::$firstCompany->id, self::$userLenderAdmin->id);
        self::$secondOrderInSameCompany = $this->createOrder(self::$firstCompany->id, self::$userLenderAdmin->id);
        self::$thirdOrderInSameCompany = $this->createOrder(self::$firstCompany->id, self::$userLenderOrderCreator->id);
        self::$firstOrderInOtherCompany = $this->createOrder(self::$secondCompany->id, self::$userLenderAdmin->id);
    }

    public function test_that_un_auth_user_cant_show_order(): void
    {
        $this->withHeader('X-Company', self::$firstCompany->id)
            ->getJson('api/v1/lender/orders/'.self::$firstOrderInSameCompany->id)
            ->assertStatus(Response::HTTP_UNAUTHORIZED)
            ->assertExactJson([
                'message' => 'Unauthenticated.',
            ]);
    }

    public function test_that_admin_user_can_show_order_in_same_company(): void
    {
        self::$firstOrderInSameCompany->load('creator', 'approver');
        $this->actingAs(self::$userLenderAdmin)
            ->withHeader('X-Company', self::$firstCompany->id)
            ->getJson('api/v1/lender/orders/'.self::$firstOrderInSameCompany->id)
            ->assertStatus(Response::HTTP_OK)
            ->assertExactJson(
                fractal(self::$firstOrderInSameCompany, (new FinancingOrderTransformer())->setCurrentUser(self::$userLenderAdmin))
                    ->parseIncludes([
                        'id',
                        'status',
                        'reference_number',
                        'customer_name',
                        'national_id',
                        'amount',
                        'selling_price',
                        'amount_formatted',
                        'selling_price_formatted',
                        'phone_country_code',
                        'phone_number',
                        'phone_number_formatted',
                        'is_verification_required',
                        'is_updatable',
                        'is_approved',
                        'is_cancellable',
                        'can_be_completed',
                        'can_create_trader_order',
                        'payment_proof_url',
                        'status_reason',
                        'creator',
                        'approver',
                        'trader_orders.id',
                        'trader_orders.reference',
                        'trader_orders.failure_reason',
                        'trader_orders.is_cancellable',
                        'trader_orders.history',
                        'trader_orders.status',
                        'trader_orders.created_at',
                        'history',
                    ])
                    ->respond()
                    ->getData(true)
            );
    }

    public function test_that_supervisor_user_can_show_order_in_same_company(): void
    {
        self::$firstOrderInSameCompany->load('creator', 'approver');
        $this->actingAs(self::$userLenderSupervisor)
            ->withHeader('X-Company', self::$firstCompany->id)
            ->getJson('api/v1/lender/orders/'.self::$firstOrderInSameCompany->id)
            ->assertStatus(Response::HTTP_OK)
            ->assertExactJson(
                fractal(self::$firstOrderInSameCompany, (new FinancingOrderTransformer())->setCurrentUser(self::$userLenderSupervisor))
                    ->parseIncludes([
                        'id',
                        'status',
                        'reference_number',
                        'customer_name',
                        'national_id',
                        'amount',
                        'selling_price',
                        'amount_formatted',
                        'selling_price_formatted',
                        'phone_country_code',
                        'phone_number',
                        'phone_number_formatted',
                        'is_verification_required',
                        'is_updatable',
                        'is_approved',
                        'is_cancellable',
                        'can_be_completed',
                        'can_create_trader_order',
                        'payment_proof_url',
                        'status_reason',
                        'creator',
                        'approver',
                        'trader_orders.id',
                        'trader_orders.reference',
                        'trader_orders.failure_reason',
                        'trader_orders.is_cancellable',
                        'trader_orders.history',
                        'trader_orders.status',
                        'trader_orders.created_at',
                        'history',
                    ])
                    ->respond()
                    ->getData(true)
            );
    }

    public function test_that_billing_user_cant_show_order_in_same_company(): void
    {
        $this->actingAs(self::$userLenderBilling)
            ->withHeader('X-Company', self::$firstCompany->id)
            ->getJson('api/v1/lender/orders/'.self::$firstOrderInSameCompany->id)
            ->assertStatus(Response::HTTP_FORBIDDEN);
    }

    public function test_that_order_creator_user_cant_show_order_not_owned_in_same_company(): void
    {
        $this->actingAs(self::$userLenderOrderCreator)
            ->withHeader('X-Company', self::$firstCompany->id)
            ->getJson('api/v1/lender/orders/'.self::$firstOrderInSameCompany->id)
            ->assertStatus(Response::HTTP_FORBIDDEN);
    }

    public function test_that_order_creator_user_can_show_order_owned_in_same_company(): void
    {
        self::$thirdOrderInSameCompany->load('creator', 'approver');
        $this->actingAs(self::$userLenderOrderCreator)
            ->withHeader('X-Company', self::$firstCompany->id)
            ->getJson('api/v1/lender/orders/'.self::$thirdOrderInSameCompany->id)
            ->assertStatus(Response::HTTP_OK)
            ->assertExactJson(
                fractal(self::$thirdOrderInSameCompany, (new FinancingOrderTransformer())->setCurrentUser(self::$userLenderOrderCreator))
                    ->parseIncludes([
                        'id',
                        'status',
                        'reference_number',
                        'customer_name',
                        'national_id',
                        'amount',
                        'selling_price',
                        'amount_formatted',
                        'selling_price_formatted',
                        'phone_country_code',
                        'phone_number',
                        'phone_number_formatted',
                        'is_verification_required',
                        'is_updatable',
                        'is_approved',
                        'is_cancellable',
                        'can_be_completed',
                        'can_create_trader_order',
                        'payment_proof_url',
                        'status_reason',
                        'creator',
                        'approver',
                        'trader_orders.id',
                        'trader_orders.reference',
                        'trader_orders.failure_reason',
                        'trader_orders.is_cancellable',
                        'trader_orders.history',
                        'trader_orders.status',
                        'trader_orders.created_at',
                        'history',
                    ])
                    ->respond()
                    ->getData(true)
            );
    }
}
