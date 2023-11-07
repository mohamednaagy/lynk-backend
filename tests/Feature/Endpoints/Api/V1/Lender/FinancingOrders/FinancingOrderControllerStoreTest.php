<?php

namespace Tests\Feature\Endpoints\Api\V1\Lender\FinancingOrders;

use App\Enums\Action;
use App\Enums\Area;
use App\Enums\CompanyNewOrderNotificationForAdminStatus;
use App\Enums\FinancingOrderStatus;
use App\Enums\Role;
use App\Enums\Subject;
use App\Models\Company;
use App\Models\User;
use App\Models\Wallet;
use App\Notifications\FinancingOrders\OrderCreated;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Notification;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;
use Tests\Traits\InteractsWithCompany;
use Tests\Traits\InteractsWithUser;

class FinancingOrderControllerStoreTest extends TestCase
{
    use RefreshDatabase, InteractsWithUser, InteractsWithCompany;

    private static Company $company;

    private static Wallet $wallet;

    private static User $userLenderAdmin;

    private static User $admin;

    private static User $mangerHasNoPermissions;

    private static User $managerHasPermissions;

    private static User $userLenderSupervisor;

    private static User $userLenderBilling;

    private static User $userLenderOrderCreator;

    private static array $orderDetails;

    /**
     * @throws BindingResolutionException
     */
    public function setUp(): void
    {
        parent::setUp();

        self::$company = $this->createLenderCompanyWithStandardOrderCost('11500000', data: [
            'notify_admins_about_new_orders' => CompanyNewOrderNotificationForAdminStatus::On,
        ]);
        self::$userLenderAdmin = $this->createLenderUser(self::$company->id, Role::LenderAdmin);
        self::$userLenderSupervisor = $this->createLenderUser(self::$company->id, Role::LenderSupervisor);
        self::$userLenderBilling = $this->createLenderUser(self::$company->id, Role::LenderBilling);
        self::$userLenderOrderCreator = $this->createLenderUser(self::$company->id, Role::LenderOrderCreator);
        self::$admin = $this->createSuperAdminUser();
        self::$mangerHasNoPermissions = $this->createSuperAdminUser(Role::Manager);
        self::$managerHasPermissions = $this->createSuperAdminUser(Role::Manager);
        $this->assignPermissionToUser(
            self::$managerHasPermissions,
            perm(Area::SuperAdmin, [Subject::FinancingOrders, Action::Edit])
        );

        self::$orderDetails = [
            'customer_name' => 'youssof',
            'national_id' => '1001280070',
            'amount' => '200',
            'selling_price' => '220',
            'phone_country_code' => 'SA',
            'phone_number' => '500112233',
            'is_verification_required' => true,
        ];
    }

    public function test_that_un_auth_user_cant_create_order(): void
    {
        $this->withHeader('X-Company', self::$company->id)
            ->postJson('api/v1/lender/orders', self::$orderDetails)
            ->assertStatus(Response::HTTP_UNAUTHORIZED)
            ->assertExactJson([
                'message' => 'Unauthenticated.',
            ]);
    }

    public function test_that_auth_user_without_national_id_cant_create_order(): void
    {
        $this->actingAs(self::$userLenderAdmin)->withHeader('X-Company', self::$company->id)
            ->postJson('api/v1/lender/orders', Arr::except(self::$orderDetails, ['national_id']))
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrorFor('national_id');
    }

    public function test_store_order_with_force_unique_reference_number(): void
    {
        self::$company->update(['force_unique_reference_number' => true]);

        $this->createOrder(self::$company->id, self::$userLenderAdmin->id, ['status' => FinancingOrderStatus::InProgress, 'reference_number' => '123']);

        $this->withHeader('X-Company', self::$company->id)
            ->actingAs(self::$userLenderAdmin)
            ->postJson('api/v1/lender/orders', array_merge(self::$orderDetails, ['reference_number' => '123']))
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrorFor('reference_number');
    }

    public function test_that_auth_user_without_amount_cant_create_order(): void
    {
        $this->actingAs(self::$userLenderAdmin)->withHeader('X-Company', self::$company->id)
            ->postJson('api/v1/lender/orders', Arr::except(self::$orderDetails, ['amount']))
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertExactJson([
                'message' => 'The amount field is required. (and 1 more error)',
                'errors' => [
                    'amount' => [
                        0 => 'The amount field is required.',
                    ],
                    'selling_price' => [
                        0 => 'The selling price must be greater than or equal to amount.',
                    ],
                ],
            ]);
    }

    public function test_that_auth_user_without_selling_price_cant_create_order(): void
    {
        $this->actingAs(self::$userLenderAdmin)->withHeader('X-Company', self::$company->id)
            ->postJson('api/v1/lender/orders', Arr::except(self::$orderDetails, ['selling_price']))
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertExactJson([
                'message' => 'The selling price field is required.',
                'errors' => [
                    'selling_price' => [
                        0 => 'The selling price field is required.',
                    ],
                ],
            ]);
    }

    public function test_that_auth_user_without_phone_country_code_cant_create_order(): void
    {
        $this->actingAs(self::$userLenderAdmin)->withHeader('X-Company', self::$company->id)
            ->postJson('api/v1/lender/orders', Arr::except(self::$orderDetails, ['phone_country_code']))
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertExactJson([
                'message' => 'The phone country code field is required when phone number is present. (and 1 more error)',
                'errors' => [
                    'phone_country_code' => [
                        'The phone country code field is required when phone number is present.',
                    ],
                    'phone_number' => [
                        'The phone number is not a valid phone number.',
                    ],
                ],
            ]);
    }

    public function test_that_auth_user_without_phone_number_cant_create_order_if_verification_required(): void
    {
        $this->actingAs(self::$userLenderAdmin)->withHeader('X-Company', self::$company->id)
            ->postJson('api/v1/lender/orders', Arr::except(self::$orderDetails, ['phone_number']))
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertExactJson([
                'message' => 'The phone number field is required when is verification required is true.',
                'errors' => [
                    'phone_number' => [
                        'The phone number field is required when is verification required is true.',
                    ],
                ],
            ]);
    }

    public function test_that_auth_user_without_phone_number_can_create_order_if_verification_not_required(): void
    {
        self::$orderDetails['is_verification_required'] = false;

        $this->actingAs(self::$userLenderAdmin)->withHeader('X-Company', self::$company->id)
            ->postJson('api/v1/lender/orders', Arr::except(self::$orderDetails, ['phone_number']))
            ->assertStatus(Response::HTTP_OK);
    }

    public function test_that_auth_user_without_is_verification_required_cant_create_order(): void
    {
        $this->actingAs(self::$userLenderAdmin)->withHeader('X-Company', self::$company->id)
            ->postJson('api/v1/lender/orders', Arr::except(self::$orderDetails, ['is_verification_required']))
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertExactJson([
                'message' => 'The is verification required field is required.',
                'errors' => [
                    'is_verification_required' => [
                        'The is verification required field is required.',
                    ],
                ],
            ]);
    }

    public function test_that_admin_user_can_create_order_with_valid_data(): void
    {
        $this->actingAs(self::$userLenderAdmin)
            ->withHeader('X-Company', self::$company->id)
            ->postJson('api/v1/lender/orders', self::$orderDetails)
            ->assertStatus(Response::HTTP_OK)
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
                    'reference_number',
                    'national_id',
                    'amount',
                    'selling_price',
                    'amount_formatted',
                    'selling_price_formatted',
                    'is_approved',
                    'status_reason',
                ],
            ]);
    }

    public function test_that_supervisor_user_can_create_order_with_valid_data(): void
    {
        $this->actingAs(self::$userLenderSupervisor)
            ->withHeader('X-Company', self::$company->id)
            ->postJson('api/v1/lender/orders', self::$orderDetails)
            ->assertStatus(Response::HTTP_OK)
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
                    'reference_number',
                    'national_id',
                    'amount',
                    'selling_price',
                    'amount_formatted',
                    'selling_price_formatted',
                    'is_approved',
                    'status_reason',
                ],
            ]);
    }

    public function test_that_billing_user_cant_create_order_with_valid_data(): void
    {
        $this->actingAs(self::$userLenderBilling)
            ->withHeader('X-Company', self::$company->id)
            ->postJson('api/v1/lender/orders', self::$orderDetails)
            ->assertStatus(Response::HTTP_FORBIDDEN);
    }

    public function test_that_order_creator_user_can_create_order_with_valid_data(): void
    {
        $this->actingAs(self::$userLenderOrderCreator)
            ->withHeader('X-Company', self::$company->id)
            ->postJson('api/v1/lender/orders', self::$orderDetails)
            ->assertStatus(Response::HTTP_OK)
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
                    'reference_number',
                    'national_id',
                    'amount',
                    'selling_price',
                    'amount_formatted',
                    'selling_price_formatted',
                    'is_approved',
                    'status_reason',
                ],
            ]);
    }

    public function test_that_admin_and_managers_get_notification_about_new_order(): void
    {
        Notification::fake();

        $this->actingAs(self::$userLenderAdmin)
            ->withHeader('X-Company', self::$company->id)
            ->postJson('api/v1/lender/orders', self::$orderDetails)
            ->assertStatus(Response::HTTP_OK);

        Notification::assertSentTo(self::$admin, OrderCreated::class);
        Notification::assertSentTo(self::$managerHasPermissions, OrderCreated::class);
        Notification::assertNotSentTo(self::$mangerHasNoPermissions, OrderCreated::class);
    }

    public function test_that_admin_and_managers_did_not_get_notification_about_new_order_when_disabled(): void
    {
        Notification::fake();
        self::$company->update(['notify_admins_about_new_orders' => CompanyNewOrderNotificationForAdminStatus::Off]);
        self::$company->refresh();

        $this->actingAs(self::$userLenderAdmin)
            ->withHeader('X-Company', self::$company->id)
            ->postJson('api/v1/lender/orders', self::$orderDetails)
            ->assertStatus(Response::HTTP_OK);

        Notification::assertNotSentTo(self::$admin, OrderCreated::class);
        Notification::assertNotSentTo(self::$managerHasPermissions, OrderCreated::class);
    }
}
