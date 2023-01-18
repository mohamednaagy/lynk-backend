<?php

namespace Tests\Feature\Endpoints\Api\V1\Admin\Lenders;

use App\Enums\Action;
use App\Enums\Area;
use App\Enums\Role;
use App\Enums\Subject;
use App\Models\Company;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Arr;
use Modules\Grantify\Facades\Grantify;
use Tests\TestCase;
use Tests\Traits\InteractsWithCompany;
use Tests\Traits\InteractsWithUser;

class LenderUserControllerUpdateTest extends TestCase
{
    use RefreshDatabase, InteractsWithUser, InteractsWithCompany;

    private static Company $lender;

    private static Wallet $wallet;

    private static User $userAdmin;

    private static User $userManager;

    private static User $userLenderAdmin;

    private static array $userDetails;

    /**
     * @return void
     *
     * @throws BindingResolutionException
     */
    public function setUp(): void
    {
        parent::setUp();

        [self::$lender, self::$wallet] = $this->createCompany('2000', ['company_cr' => '12345678910']);
        self::$userAdmin = $this->createSuperAdminUser();
        self::$userManager = $this->createSuperAdminUser(Role::Manager);
        $this->assignPermissionToUser(self::$userManager, perm(Area::SuperAdmin, [Subject::LenderUsers, Action::Edit]));

        self::$userLenderAdmin = $this->createLenderUser(self::$lender->id, Role::LenderAdmin);
        self::$userDetails = [
            'first_name' => 'first_name',
            'last_name' => 'last_name',
            'phone_number' => '500112233',
            'phone_country_code' => 'SA',
            'email' => 'user@bim.com',
            'redirect_url' => 'http://bimventures.com',
            'role' => Role::LenderAdmin,
        ];
    }

    /**
     * @return void
     */
    public function test_that_un_auth_user_cant_update_lender_user(): void
    {
        $this->putJson('api/v1/admin/lenders/'.self::$lender->id.'/users/'.self::$userLenderAdmin->id, self::$userDetails)
            ->assertUnauthorized()
            ->assertExactJson([
                'message' => __('Unauthenticated.'),
            ]);
    }

    /**
     * @return void
     */
    public function test_that_auth_admin_user_can_update_lender_user_with_valid_data(): void
    {
        $this->actingAs(self::$userAdmin)
            ->putJson('api/v1/admin/lenders/'.self::$lender->id.'/users/'.self::$userLenderAdmin->id, self::$userDetails)
            ->assertOk()
            ->assertExactJson([
                'data' => [],
            ]);
    }

    /**
     * @return void
     */
    public function test_that_auth_manager_user_can_update_lender_user_with_valid_data(): void
    {
        $this->actingAs(self::$userManager)
            ->putJson('api/v1/admin/lenders/'.self::$lender->id.'/users/'.self::$userLenderAdmin->id, self::$userDetails)
            ->assertOk()
            ->assertExactJson([
                'data' => [],
            ]);
    }

    /**
     * @return void
     */
    public function test_that_auth_manager_user_without_permissions_cant_update_lender_user_with_valid_data(): void
    {
        Grantify::syncPermissionToModel(self::$userManager, []);

        $this->actingAs(self::$userManager)
            ->putJson('api/v1/admin/lenders/'.self::$lender->id.'/users/'.self::$userLenderAdmin->id, self::$userDetails)
            ->assertForbidden();
    }

    /**
     * @return void
     */
    public function test_that_auth_admin_user_cant_update_lender_user_without_first_name(): void
    {
        $this->actingAs(self::$userAdmin)
            ->putJson('api/v1/admin/lenders/'.self::$lender->id.'/users/'.self::$userLenderAdmin->id, Arr::except(self::$userDetails, 'first_name'))
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

    /**
     * @return void
     */
    public function test_that_auth_admin_user_cant_update_lender_user_without_last_name(): void
    {
        $this->actingAs(self::$userAdmin)
            ->putJson('api/v1/admin/lenders/'.self::$lender->id.'/users/'.self::$userLenderAdmin->id, Arr::except(self::$userDetails, 'last_name'))
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

    /**
     * @return void
     */
    public function test_that_auth_admin_user_cant_update_lender_user_without_phone_country_code(): void
    {
        $this->actingAs(self::$userAdmin)
            ->putJson('api/v1/admin/lenders/'.self::$lender->id.'/users/'.self::$userLenderAdmin->id, Arr::except(self::$userDetails, 'phone_country_code'))
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

    /**
     * @return void
     */
    public function test_that_auth_admin_user_cant_update_lender_user_without_phone_number(): void
    {
        $this->actingAs(self::$userAdmin)
            ->putJson('api/v1/admin/lenders/'.self::$lender->id.'/users/'.self::$userLenderAdmin->id, Arr::except(self::$userDetails, 'phone_number'))
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

    /**
     * @return void
     */
    public function test_that_auth_admin_user_cant_update_lender_user_without_email(): void
    {
        $this->actingAs(self::$userAdmin)
            ->putJson('api/v1/admin/lenders/'.self::$lender->id.'/users/'.self::$userLenderAdmin->id, Arr::except(self::$userDetails, 'email'))
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

    /**
     * @return void
     */
    public function test_that_auth_admin_user_can_update_lender_user_without_redirect_url(): void
    {
        $this->actingAs(self::$userAdmin)
            ->putJson('api/v1/admin/lenders/'.self::$lender->id.'/users/'.self::$userLenderAdmin->id, Arr::except(self::$userDetails, 'redirect_url'))
            ->assertOk()
            ->assertExactJson([
                'data' => [],
            ]);
    }

    /**
     * @return void
     */
    public function test_that_auth_admin_user_cant_update_lender_user_without_role(): void
    {
        $this->actingAs(self::$userAdmin)
            ->putJson('api/v1/admin/lenders/'.self::$lender->id.'/users/'.self::$userLenderAdmin->id, Arr::except(self::$userDetails, 'role'))
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

    /**
     * @return void
     */
    public function test_that_auth_admin_user_cant_update_lender_user_with_lender_api_user_role(): void
    {
        $this->actingAs(self::$userAdmin)
            ->putJson('api/v1/admin/lenders/'.self::$lender->id.'/users/'.self::$userLenderAdmin->id, array_merge(self::$userDetails, ['role' => Role::LenderApiUser]))
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
}
