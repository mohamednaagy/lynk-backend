<?php

namespace Endpoints\Api\V1\Admin\Commodity\CommoditySupplier\Users;

use App\Enums\Action;
use App\Enums\Area;
use App\Enums\Role;
use App\Enums\Subject;
use App\Models\CompanySupplierDetail;
use App\Models\User;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Arr;
use Modules\Grantify\Facades\Grantify;
use Tests\TestCase;
use Tests\Traits\InteractsWithCommoditySupplier;
use Tests\Traits\InteractsWithUser;

class SupplierUserControllerStoreTest extends TestCase
{
    use InteractsWithCommoditySupplier, InteractsWithUser, RefreshDatabase;

    private static CompanySupplierDetail $supplier;

    private static User $userAdmin;

    private static User $userManager;

    private static array $userDetails;

    private static string $endpoint;

    /**
     * @throws BindingResolutionException
     */
    public function setUp(): void
    {
        parent::setUp();

        self::$supplier = $this->createCommoditySupplier(
            'new legal name'.rand(11, 999),
            'new unique name'.rand(11, 999),
            '1001280070'
        );
        self::$userAdmin = $this->createSuperAdminUser();
        self::$userManager = $this->createSuperAdminUser(Role::Manager);
        $this->assignPermissionToUser(self::$userManager, perm(Area::SuperAdmin, [Subject::CommoditySupplierUsers, Action::Create]));
        self::$endpoint = 'api/v1/admin/commodity-suppliers/'.self::$supplier->id.'/users';
        self::$userDetails = [
            'first_name' => 'first_name',
            'last_name' => 'last_name',
            'phone_number' => '500112233',
            'phone_country_code' => 'SA',
            'email' => 'user@lynk.sa',
            'redirect_url' => 'http://lynk.sa',
            'role' => Role::SupplierAdmin,
        ];
    }

    public function test_un_auth_user_cant_store_supplier_user(): void
    {
        $this->postJson(self::$endpoint, self::$userDetails)
            ->assertUnauthorized()
            ->assertExactJson([
                'message' => __('Unauthenticated.'),
            ]);
    }

    public function test_auth_admin_user_can_store_supplier_user_with_valid_data_successfully(): void
    {
        $this->actingAs(self::$userAdmin)
            ->postJson(self::$endpoint, self::$userDetails)
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'first_name',
                    'last_name',
                    'email',
                    'phone_number',
                    'phone_country_code',
                    'formatted_phone_number',
                ],
            ]);
    }

    public function test_auth_manager_user_can_store_supplier_user_with_valid_data_successfully(): void
    {
        $this->actingAs(self::$userManager)
            ->postJson(self::$endpoint, self::$userDetails)
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'first_name',
                    'last_name',
                    'email',
                    'phone_number',
                    'phone_country_code',
                    'formatted_phone_number',
                ],
            ]);
    }

    public function test_auth_manager_user_without_permissions_cant_store_supplier_user_with_valid_data(): void
    {
        Grantify::syncPermissionToModel(self::$userManager, []);

        $this->actingAs(self::$userManager)
            ->postJson(self::$endpoint, self::$userDetails)
            ->assertForbidden();
    }

    public function test_auth_admin_user_cant_store_supplier_user_without_first_name(): void
    {
        $this->actingAs(self::$userAdmin)
            ->postJson(self::$endpoint, Arr::except(self::$userDetails, 'first_name'))
            ->assertUnprocessable()
            ->assertExactJson([
                'message' => 'The first name field is required.',
                'errors' => [
                    'first_name' => [
                        'The first name field is required.',
                    ],
                ],
            ]);
    }

    public function test_auth_admin_user_cant_store_supplier_user_without_last_name(): void
    {
        $this->actingAs(self::$userAdmin)
            ->postJson(self::$endpoint, Arr::except(self::$userDetails, 'last_name'))
            ->assertUnprocessable()
            ->assertExactJson([
                'message' => 'The last name field is required.',
                'errors' => [
                    'last_name' => [
                        'The last name field is required.',
                    ],
                ],
            ]);
    }

    public function test_auth_admin_user_cant_store_supplier_user_without_phone_country_code(): void
    {
        $this->actingAs(self::$userAdmin)
            ->postJson(self::$endpoint, Arr::except(self::$userDetails, 'phone_country_code'))
            ->assertUnprocessable()
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

    public function test_auth_admin_user_cant_store_supplier_user_without_phone_number(): void
    {
        $this->actingAs(self::$userAdmin)
            ->postJson(self::$endpoint, Arr::except(self::$userDetails, 'phone_number'))
            ->assertUnprocessable()
            ->assertExactJson([
                'message' => 'The phone number field is required.',
                'errors' => [
                    'phone_number' => [
                        'The phone number field is required.',
                    ],
                ],
            ]);
    }

    public function test_auth_admin_user_cant_store_supplier_user_without_email(): void
    {
        $this->actingAs(self::$userAdmin)
            ->postJson(self::$endpoint, Arr::except(self::$userDetails, 'email'))
            ->assertUnprocessable()
            ->assertExactJson([
                'message' => 'The email field is required.',
                'errors' => [
                    'email' => [
                        'The email field is required.',
                    ],
                ],
            ]);
    }

    public function test_auth_admin_user_cant_store_supplier_user_without_redirect_url(): void
    {
        $this->actingAs(self::$userAdmin)
            ->postJson(self::$endpoint, Arr::except(self::$userDetails, 'redirect_url'))
            ->assertUnprocessable()
            ->assertExactJson([
                'message' => 'The redirect url field is required.',
                'errors' => [
                    'redirect_url' => [
                        'The redirect url field is required.',
                    ],
                ],
            ]);
    }

    public function test_auth_admin_user_cant_store_supplier_user_without_role(): void
    {
        $this->actingAs(self::$userAdmin)
            ->postJson(self::$endpoint, Arr::except(self::$userDetails, 'role'))
            ->assertUnprocessable()
            ->assertExactJson([
                'message' => 'The role field is required.',
                'errors' => [
                    'role' => [
                        'The role field is required.',
                    ],
                ],
            ]);
    }

    public function test_auth_admin_user_cant_store_supplier_user_with_supplier_api_user_role(): void
    {
        $this->actingAs(self::$userAdmin)
            ->postJson(self::$endpoint, array_merge(self::$userDetails, ['role' => Role::SupplierApiAdmin]))
            ->assertUnprocessable()
            ->assertExactJson([
                'message' => 'The selected role is invalid.',
                'errors' => [
                    'role' => [
                        'The selected role is invalid.',
                    ],
                ],
            ]);
    }

    public function test_admin_user_cant_store_supplier_user_with_same_email_for_another_user_in_same_company(): void
    {
        User::factory()->create([
            'company_id' => self::$supplier->id,
            'email' => 'user@bim.com',
        ]);
        $this->actingAs(self::$userAdmin)
            ->postJson(self::$endpoint, self::$userDetails)
            ->assertUnprocessable()
            ->assertExactJson([
                'message' => 'The email has already been taken.',
                'errors' => [
                    'email' => [
                        'The email has already been taken.',
                    ],
                ],
            ]);
    }
}
