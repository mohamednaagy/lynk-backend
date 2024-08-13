<?php

namespace Endpoints\Api\V1\Admin\Commodity\CommoditySupplier\Users;

use App\Enums\Role;
use App\Mail\ChangeEmail;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;
use Tests\Traits\InteractsWithSupplier;
use Tests\Traits\InteractsWithUser;

class SupplierUserControllerUpdateTest extends TestCase
{
    use InteractsWithSupplier, InteractsWithUser, RefreshDatabase;

    private static Supplier $supplier;

    private static Wallet $wallet;

    private static User $userAdmin;

    private static User $userSupervisor;

    private static User $userManager;

    private static User $userSupplierAdmin;

    private static array $userDetails;

    private static string $endpoint;

    /**
     * @throws BindingResolutionException
     */
    public function setUp(): void
    {
        parent::setUp();

        self::$supplier = $this->createSupplier();
        self::$userAdmin = $this->createSuperAdminUser();
        self::$userSupervisor = $this->createSupervisorUser();
        self::$userSupplierAdmin = $this->createSupplierUser(self::$supplier->id);

        self::$userDetails = [
            'first_name' => 'first_name',
            'last_name' => 'last_name',
            'phone_number' => '500112233',
            'phone_country_code' => 'SA',
            'email' => 'user@bim.com',
            'redirect_url' => 'http://bimventures.com',
            'role' => Role::SupplierAdmin,
        ];
        self::$endpoint = 'api/v1/admin/commodity-suppliers/'.self::$supplier->id.'/users/'.self::$userSupplierAdmin->id;
    }

    public function test_that_un_auth_user_cant_update_supplier_user(): void
    {
        $this->putJson(self::$endpoint, self::$userDetails)
            ->assertUnauthorized()
            ->assertExactJson([
                'message' => __('Unauthenticated.'),
            ]);
    }

    public function test_that_auth_admin_user_can_update_supplier_user_with_valid_data(): void
    {
        $this->actingAs(self::$userAdmin)
            ->putJson(self::$endpoint, self::$userDetails)
            ->assertOk()
            ->assertExactJson([
                'data' => [],
            ]);
    }

    public function test_that_auth_admin_user_cant_update_supplier_user_without_first_name(): void
    {
        $this->actingAs(self::$userAdmin)
            ->putJson(self::$endpoint, Arr::except(self::$userDetails, 'first_name'))
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

    public function test_that_auth_admin_user_cant_update_supplier_user_without_last_name(): void
    {
        $this->actingAs(self::$userAdmin)
            ->putJson(self::$endpoint, Arr::except(self::$userDetails, 'last_name'))
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

    public function test_that_auth_admin_user_cant_update_supplier_user_without_phone_country_code(): void
    {
        $this->actingAs(self::$userAdmin)
            ->putJson(self::$endpoint, Arr::except(self::$userDetails, 'phone_country_code'))
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

    public function test_that_auth_admin_user_cant_update_supplier_user_without_phone_number(): void
    {
        $this->actingAs(self::$userAdmin)
            ->putJson(self::$endpoint, Arr::except(self::$userDetails, 'phone_number'))
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

    public function test_that_auth_admin_user_cant_update_supplier_user_without_email(): void
    {
        $this->actingAs(self::$userAdmin)
            ->putJson(self::$endpoint, Arr::except(self::$userDetails, 'email'))
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

    public function test_that_auth_admin_user_can_update_supplier_user_without_redirect_url(): void
    {
        $this->actingAs(self::$userAdmin)
            ->putJson(self::$endpoint, Arr::except(self::$userDetails, 'redirect_url'))
            ->assertOk()
            ->assertExactJson([
                'data' => [],
            ]);
    }

    public function test_that_auth_admin_user_cant_update_supplier_user_without_role(): void
    {
        $this->actingAs(self::$userAdmin)
            ->putJson(self::$endpoint, Arr::except(self::$userDetails, 'role'))
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

    public function test_that_auth_admin_user_cant_update_supplier_user_with_supplier_api_user_role(): void
    {
        $this->actingAs(self::$userAdmin)
            ->putJson(self::$endpoint, array_merge(self::$userDetails, ['role' => Role::LenderApiUser]))
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

    public function test_supplier_user_change_email_and_make_user_not_verified(): void
    {
        Mail::fake();
        $this->assertNotEquals(self::$userSupplierAdmin->email, self::$userDetails['email']);
        $this->assertNotNull(self::$userSupplierAdmin->email_verified_at);
        $this->actingAs(self::$userAdmin)
            ->putJson(self::$endpoint, self::$userDetails)
            ->assertOk()
            ->assertExactJson([
                'data' => [],
            ]);
        Mail::assertQueued(ChangeEmail::class);
        $this->assertNull(self::$userSupplierAdmin->refresh()->email_verified_at);

    }
}
