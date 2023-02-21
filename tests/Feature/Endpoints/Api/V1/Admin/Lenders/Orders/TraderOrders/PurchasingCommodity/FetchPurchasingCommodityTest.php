<?php

namespace Tests\Feature\Endpoints\Api\V1\Admin\Lenders\Orders\TraderOrders\PurchasingCommodity;

use App\Enums\Area;
use App\Enums\FinancingOrderStatus;
use App\Enums\TraderOrderStatus;
use App\Models\Company;
use App\Models\TraderOrder;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Response;
use Tests\TestCase;
use Tests\Traits\AssertsAccessByRoleAndArea;

class FetchPurchasingCommodityTest extends TestCase
{
    use RefreshDatabase, AssertsAccessByRoleAndArea;

    const BaseUrl = 'api/v1/admin';

    private static Company $lender;

    private static User $userLender;

    private static User $superAdminUser;

    private static Builder|Model $financingOrder;

    private static TraderOrder $traderOrder;

    private static string $getPurchasingCommodityUrl;

    /**
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        self::$superAdminUser = $this->createSuperAdminUser();
        [self::$lender] = $this->createLenderCompany('2000', [
            'company_cr' => '1234567891',
        ]);
        self::$userLender = $this->createLenderUser(self::$lender->id);
        self::$financingOrder = $this->createOrder(
            self::$lender->id,
            self::$userLender->id,
            [
                'is_verification_required' => true,
                'status' => FinancingOrderStatus::WaitingPurchasingCommodity,
            ]
        );

        // create trader order
        self::$traderOrder = self::$financingOrder->traderOrders()->create([
            'provider' => 'dmcc',
            'reference' => '123456789',
            'status' => TraderOrderStatus::InProgress,
        ]);

        self::$getPurchasingCommodityUrl = self::BaseUrl.
            '/orders/'.
            self::$financingOrder->getOriginal('id').
            '/trader-orders/'.
            self::$traderOrder->getOriginal('id').
            '/purchasing-commodity';
    }

    /**
     * @return void
     */
    public function test_that_unauth_user_cant_fetch_purchasing_commodity(): void
    {
        $this->getJson(self::$getPurchasingCommodityUrl)
            ->assertStatus(Response::HTTP_UNAUTHORIZED)
            ->assertExactJson([
                'message' => 'Unauthenticated.',
            ]);
    }

    /**
     * @return void
     */
    public function test_that_other_area_roles_of_not_super_admin_area_cant_fetch_purchasing_commodity(): void
    {
        $this->assertStatusCodeForAllRolesExceptForArea(
            Response::HTTP_FORBIDDEN,
            [
                Area::SuperAdmin,
            ],
            function ($user, $role) {
                return $this->actingAs($user)
                    ->getJson(self::$getPurchasingCommodityUrl);
            }
        );
    }

    /**
     * @return void
     */
    public function test_fetch_purchasing_commodity_succeed(): void
    {
        $this->actingAs(self::$superAdminUser)
            ->getJson(self::$getPurchasingCommodityUrl)
            ->assertJsonStructure([
                'data' => [
                    'purchasing_commodity_information',
                ],
            ]);
    }
}
