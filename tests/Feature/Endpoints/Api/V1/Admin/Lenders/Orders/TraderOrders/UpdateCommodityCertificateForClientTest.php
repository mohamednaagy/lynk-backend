<?php

namespace Endpoints\Api\V1\Admin\Lenders\Orders\TraderOrders;

use App\Enums\Action;
use App\Enums\Area;
use App\Enums\FinancingOrderHistory;
use App\Enums\FinancingOrderStatus;
use App\Enums\Role;
use App\Enums\Subject;
use App\Enums\TraderOrderStatus;
use App\Models\Company;
use App\Models\TraderOrder;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Response;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;
use Tests\Traits\AssertsAccessByRoleAndArea;

class UpdateCommodityCertificateForClientTest extends TestCase
{
    use RefreshDatabase, AssertsAccessByRoleAndArea;

    const BaseUrl = 'api/v1/admin';

    private static Company $lender;

    private static User $userLender;

    private static User $superAdminUser;

    private static User $managerUser;

    private static Builder|Model $financingOrder;

    private static TraderOrder $traderOrder;

    private static string $endpoint;

    private static string $fileName;

    /**
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        self::$superAdminUser = $this->createSuperAdminUser();
        self::$managerUser = $this->createSuperAdminUser(Role::Manager);

        [self::$lender] = $this->createLenderCompany('2000', ['company_cr' => '1234567891']);
        self::$userLender = $this->createLenderUser(self::$lender->id);
        self::$financingOrder = $this->createOrder(
            self::$lender->id,
            self::$userLender->id,
            [
                'is_verification_required' => true,
                'status' => FinancingOrderStatus::ContractSigned,
            ]
        );

        // create trader order
        self::$traderOrder = self::$financingOrder->traderOrders()->create([
            'provider' => 'dmcc',
            'reference' => 1,
            'status' => TraderOrderStatus::InProgress,
            'amount' => 1,
            'product' => 'product',
            'quantity' => 1,
            'warehouse' => 'warehouse',
            'owner' => 'owner',
        ]);

        self::$traderOrder->traderHistories()->create([
            'action' => FinancingOrderHistory::ContractSigned,
        ]);

        self::$endpoint = self::BaseUrl.
            '/orders/'.
            self::$financingOrder->id.
            '/trader-orders/'.
            self::$traderOrder->id.
            '/selling-commodity-to-client';

        self::$fileName = self::$traderOrder->provider.'-'.self::$traderOrder->reference.'.pdf';
    }

    /**
     * @return void
     */
    public function test_that_unauth_user_cant_update_selling_commodity_certificate(): void
    {
        $this->postJson(self::$endpoint)
            ->assertStatus(Response::HTTP_UNAUTHORIZED)
            ->assertExactJson([
                'message' => 'Unauthenticated.',
            ]);
    }

    /**
     * @return void
     */
    public function test_that_other_area_roles_of_not_super_admin_area_cant_update_selling_commodity_certificate(): void
    {
        $this->assertStatusCodeExceptForPermissions(
            Response::HTTP_FORBIDDEN,
            [
                Area::SuperAdmin => [
                    [Subject::All, Action::Manage],
                    [Subject::FinancingOrders, Action::Edit],
                    [Subject::FinancingOrders, Action::Manage],
                ],
            ],
            function ($user, $role, $permission) {
                return $this->actingAs($user)
                    ->postJson(self::$endpoint);
            }
        );
    }

    /**
     * @return void
     */
    public function test_that_manager_with_proper_permissions_can_access(): void
    {
        $this->assignPermissionToUser(self::$managerUser, perm(Area::SuperAdmin, [Subject::FinancingOrders, Action::Edit]));

        $this->actingAs(self::$managerUser)
            ->postJson(self::$endpoint, [
                'document' => UploadedFile::fake()->create(self::$fileName),
                'automatically_generate_file' => false,
            ])
            ->assertStatus(Response::HTTP_OK);
    }

    /**
     * @return void
     */
    public function test_that_manager_without_proper_permissions_cannot_access(): void
    {
        $this->actingAs(self::$managerUser)
            ->postJson(self::$endpoint, [
                'document' => UploadedFile::fake()->create(self::$fileName),
                'automatically_generate_file' => false,
            ])
            ->assertStatus(Response::HTTP_FORBIDDEN);
    }

    /**
     * @return void
     */
    public function test_update_selling_commodity_certificate_with_document_succeed(): void
    {
        $this->actingAs(self::$superAdminUser)
            ->postJson(self::$endpoint, [
                'document' => UploadedFile::fake()->create(self::$fileName),
                'automatically_generate_file' => false,
            ])
            ->assertJsonStructure([
                'data' => [
                    'url',
                ],
            ]);

        $this->assertTrue(self::$financingOrder->fresh()->status->is(FinancingOrderStatus::CommoditySoldToCustomer));

        self::$financingOrder->update(['status' => FinancingOrderStatus::ClientWakalaCompleted]);

        $this->actingAs(self::$superAdminUser)
            ->postJson(self::$endpoint, [
                'document' => UploadedFile::fake()->create(self::$fileName),
                'automatically_generate_file' => false,
            ])
            ->assertJsonStructure([
                'data' => [
                    'url',
                ],
            ]);

        $this->assertTrue(self::$financingOrder->fresh()->status->is(FinancingOrderStatus::ClientWakalaCompleted));
    }

    /**
     * @return void
     */
    public function test_update_selling_commodity_certificate_with_document_with_trader_order_not_in_progress_succeed(): void
    {
        self::$traderOrder->update(['status' => TraderOrderStatus::Completed]);

        self::$financingOrder->update(['status' => FinancingOrderStatus::ClientWakalaCompleted]);

        $this->actingAs(self::$superAdminUser)
            ->postJson(self::$endpoint, [
                'document' => UploadedFile::fake()->create(self::$fileName),
                'automatically_generate_file' => false,
            ])
            ->assertJsonStructure([
                'data' => [
                    'url',
                ],
            ]);

        $this->assertTrue(self::$financingOrder->fresh()->status->is(FinancingOrderStatus::ClientWakalaCompleted));
    }

    /**
     * @return void
     */
    public function test_update_selling_commodity_certificate_with_auto_generation_succeed(): void
    {
        $this->actingAs(self::$superAdminUser)
            ->postJson(self::$endpoint, [
                'document' => null,
                'automatically_generate_file' => true,
            ])
            ->assertJsonStructure([
                'data' => [
                    'url',
                ],
            ]);

        self::$financingOrder->update(['status' => FinancingOrderStatus::ClientWakalaCompleted]);

        $this->actingAs(self::$superAdminUser)
            ->postJson(self::$endpoint, [
                'document' => null,
                'automatically_generate_file' => true,
            ])
            ->assertJsonStructure([
                'data' => [
                    'url',
                ],
            ]);

        $this->assertTrue(self::$financingOrder->fresh()->status->is(FinancingOrderStatus::ClientWakalaCompleted));
    }

    /**
     * @return void
     */
    public function test_update_selling_commodity_certificate_with_auto_generation_with_trader_order_not_in_progress_succeed(): void
    {
        self::$traderOrder->update(['status' => TraderOrderStatus::Completed]);

        self::$financingOrder->update(['status' => FinancingOrderStatus::ClientWakalaCompleted]);

        $this->actingAs(self::$superAdminUser)
            ->postJson(self::$endpoint, [
                'document' => null,
                'automatically_generate_file' => true,
            ])
            ->assertJsonStructure([
                'data' => [
                    'url',
                ],
            ]);

        $this->assertTrue(self::$financingOrder->fresh()->status->is(FinancingOrderStatus::ClientWakalaCompleted));
    }
}
