<?php

namespace Tests\Feature\Endpoints\Api\V1\Lender\FinancingOrders;

use App\Enums\Role;
use App\Models\Company;
use App\Models\User;
use App\Transformers\FinancingOrderTransformer;
use Bavix\Wallet\Models\Wallet;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;
use Tests\Traits\InteractsWithLender;

class FinancingOrderControllerShowTest extends TestCase
{
    use RefreshDatabase, InteractsWithLender;

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

    /**
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        [self::$firstCompany, self::$firstWallet] = $this->createCompany('2000', ['company_cr' => '12345678910']);
        [self::$secondCompany, self::$secondWallet] = $this->createCompany('3000', ['company_cr' => '12345678911']);
        self::$userLenderAdmin = $this->createLenderUser(self::$firstCompany->id, Role::LenderAdmin, 'lenderAdmin@bim.com');
        self::$userLenderSupervisor = $this->createLenderUser(self::$firstCompany->id, Role::LenderSupervisor, 'lenderSupervisor@bim.com');
        self::$userLenderBilling = $this->createLenderUser(self::$firstCompany->id, Role::LenderBilling, 'lenderBilling@bim.com');
        self::$userLenderOrderCreator = $this->createLenderUser(self::$firstCompany->id, Role::LenderOrderCreator, 'lenderOrderCreator@bim.com');
        self::$firstOrderInSameCompany = $this->createOrder(self::$firstCompany->id, self::$userLenderAdmin->id);
        self::$secondOrderInSameCompany = $this->createOrder(self::$firstCompany->id, self::$userLenderAdmin->id);
        self::$thirdOrderInSameCompany = $this->createOrder(self::$firstCompany->id, self::$userLenderOrderCreator->id);
        self::$firstOrderInOtherCompany = $this->createOrder(self::$secondCompany->id, self::$userLenderAdmin->id);
    }

    /**
     * @return void
     */
    public function test_that_un_auth_user_cant_show_order(): void
    {
        $this->withHeader('X-Company', self::$firstCompany->id)
            ->getJson('api/v1/lender/orders/'.self::$firstOrderInSameCompany->id)
            ->assertStatus(Response::HTTP_UNAUTHORIZED)
            ->assertExactJson([
                'message' => 'Unauthenticated.',
            ]);
    }

    /**
     * @return void
     */
    public function test_that_admin_user_can_show_order_in_same_company(): void
    {
        $this->actingAs(self::$userLenderAdmin)
            ->withHeader('X-Company', self::$firstCompany->id)
            ->getJson('api/v1/lender/orders/'.self::$firstOrderInSameCompany->id)
            ->assertStatus(Response::HTTP_OK)
            ->assertExactJson(
                fractal(self::$firstOrderInSameCompany, new FinancingOrderTransformer())
                    ->parseIncludes(['creator', 'approver', 'history'])
                    ->respond()->getData(true)
            );
    }

    /**
     * @return void
     */
    public function test_that_supervisor_user_can_show_order_in_same_company(): void
    {
        $this->actingAs(self::$userLenderSupervisor)
            ->withHeader('X-Company', self::$firstCompany->id)
            ->getJson('api/v1/lender/orders/'.self::$firstOrderInSameCompany->id)
            ->assertStatus(Response::HTTP_OK)
            ->assertExactJson(
                fractal(self::$firstOrderInSameCompany, new FinancingOrderTransformer())
                    ->parseIncludes(['creator', 'approver', 'history'])
                    ->respond()->getData(true)
            );
    }

    /**
     * @return void
     */
    public function test_that_billing_user_cant_show_order_in_same_company(): void
    {
        $this->actingAs(self::$userLenderBilling)
            ->withHeader('X-Company', self::$firstCompany->id)
            ->getJson('api/v1/lender/orders/'.self::$firstOrderInSameCompany->id)
            ->assertStatus(Response::HTTP_FORBIDDEN);
    }

    /**
     * @return void
     */
    public function test_that_order_creator_user_cant_show_order_not_owned_in_same_company(): void
    {
        $this->actingAs(self::$userLenderOrderCreator)
            ->withHeader('X-Company', self::$firstCompany->id)
            ->getJson('api/v1/lender/orders/'.self::$firstOrderInSameCompany->id)
            ->assertStatus(Response::HTTP_FORBIDDEN);
    }

    /**
     * @return void
     */
    public function test_that_order_creator_user_can_show_order_owned_in_same_company(): void
    {
        $this->actingAs(self::$userLenderOrderCreator)
            ->withHeader('X-Company', self::$firstCompany->id)
            ->getJson('api/v1/lender/orders/'.self::$thirdOrderInSameCompany->id)
            ->assertStatus(Response::HTTP_OK)
            ->assertExactJson(
                fractal(self::$thirdOrderInSameCompany, new FinancingOrderTransformer())
                    ->parseIncludes(['creator', 'approver', 'history'])
                    ->respond()->getData(true)
            );
    }
}
