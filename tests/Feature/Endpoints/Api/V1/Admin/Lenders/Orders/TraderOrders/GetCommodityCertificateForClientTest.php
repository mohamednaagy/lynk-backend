<?php

namespace Endpoints\Api\V1\Admin\Lenders\Orders\TraderOrders;

use App\Enums\Action;
use App\Enums\Area;
use App\Enums\FinancingOrderStatus;
use App\Enums\MediaCollections\TraderOrderMediaCollection;
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
use Spatie\MediaLibrary\MediaCollections\Exceptions\FileDoesNotExist;
use Spatie\MediaLibrary\MediaCollections\Exceptions\FileIsTooBig;
use Tests\TestCase;
use Tests\Traits\AssertsAccessByRoleAndArea;

class GetCommodityCertificateForClientTest extends TestCase
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
                'status' => FinancingOrderStatus::InProgress,
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

        self::$endpoint = self::BaseUrl.
            '/orders/'.
            self::$financingOrder->getOriginal('id').
            '/trader-orders/'.
            self::$traderOrder->getOriginal('id').
            '/selling-commodity-to-client';

        self::$fileName = self::$traderOrder->provider.'-'.self::$traderOrder->reference.'.pdf';
    }

    /**
     * @return void
     */
    public function test_that_unauth_user_cant_get_selling_commodity_certificate(): void
    {
        $this->getJson(self::$endpoint)
            ->assertStatus(Response::HTTP_UNAUTHORIZED)
            ->assertExactJson([
                'message' => 'Unauthenticated.',
            ]);
    }

    /**
     * @return void
     */
    public function test_that_other_area_roles_of_not_super_admin_area_cant_get_selling_commodity_certificate(): void
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
                    ->getJson(self::$endpoint);
            }
        );
    }

    /**
     * @return void
     *
     * @throws FileDoesNotExist
     * @throws FileIsTooBig
     */
    public function test_get_selling_commodity_certificate_succeed(): void
    {
        $file = UploadedFile::fake()->image(self::$fileName);

        self::$traderOrder->addMedia($file)
            ->toMediaCollection(TraderOrderMediaCollection::SellingCommodityToCustomer);

        $this->actingAs(self::$superAdminUser)
            ->getJson(self::$endpoint)
            ->assertJsonStructure([
                'data' => [
                    'url',
                ],
            ]);
    }

    /**
     * @return void
     */
    public function test_that_manager_with_proper_permissions_can_access(): void
    {
        $this->assignPermissionToUser(self::$managerUser, perm(Area::SuperAdmin, [Subject::FinancingOrders, Action::Show]));

        $this->actingAs(self::$managerUser)
            ->getJson(self::$endpoint)
            ->assertStatus(Response::HTTP_OK);
    }

    /**
     * @return void
     */
    public function test_that_manager_without_proper_permissions_cannot_access(): void
    {
        $this->actingAs(self::$managerUser)
            ->getJson(self::$endpoint)
            ->assertStatus(Response::HTTP_FORBIDDEN);
    }
}
