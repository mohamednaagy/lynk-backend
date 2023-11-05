<?php

namespace Tests\Feature\Endpoints\Api\V1\Admin\Settings\Lender;

use App\Enums\Action;
use App\Enums\Area;
use App\Enums\CompanyNewOrderNotificationForAdminStatus;
use App\Enums\CompanyStatus;
use App\Enums\Role;
use App\Enums\Subject;
use App\Models\Company;
use App\Models\User;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Arr;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;
use Tests\Traits\InteractsWithCompany;
use Tests\Traits\InteractsWithSettings;
use Tests\Traits\InteractsWithUser;

class LenderSettingsUpdateTest extends TestCase
{
    use RefreshDatabase, InteractsWithSettings, InteractsWithUser, InteractsWithCompany;

    const BaseUrl = 'api/v1/admin/settings/lender';

    private static User $admin;

    private static User $manager;

    private static User $managerHasPermission;

    private static Company $company;

    private static User $userLenderAdmin;

    private static $lenderSettings;

    private static array $lenderSettingsData = [];

    public function setUp(): void
    {
        parent::setUp();

        self::$admin = $this->createSuperAdminUser();
        self::$manager = $this->createSuperAdminUser(Role::Manager);
        self::$managerHasPermission = $this->createSuperAdminUser(Role::Manager);
        $this->assignPermissionToUser(self::$managerHasPermission, perm(Area::SuperAdmin, [Subject::LenderAreaSettings, Action::Edit]));

        [self::$company] = $this->createCompany('2000', ['company_cr' => '12345678910']);
        self::$userLenderAdmin = $this->createLenderUser(self::$company->id, Role::LenderAdmin);
        self::$lenderSettings = $this->getSettingsClass(Area::Lender);
        self::$lenderSettingsData = [
            'default_order_cost' => 150,
            'email_verification_enabled' => true,
            'notify_admins_about_new_orders' => CompanyNewOrderNotificationForAdminStatus::On,
            'default_does_order_require_approval' => false,
            'require_initiate_trade_request' => false,
            'notify_borrowers_about_order_updates' => false,
            'default_company_registration_status' => CompanyStatus::UnderReview,
            'default_company_status_created_by_operation' => CompanyStatus::UnderReview,
        ];
    }

    public function test_that_un_auth_user_cant_update_lender_settings_failed(): void
    {
        $this->putJson(self::BaseUrl)
            ->assertStatus(Response::HTTP_UNAUTHORIZED)
            ->assertExactJson([
                'message' => __('Unauthenticated.'),
            ]);
    }

    public function test_that_un_authorized_user_without_right_role_cant_update_lender_settings_failed(): void
    {
        $this->actingAs(self::$userLenderAdmin)
            ->getJson(self::BaseUrl)
            ->assertStatus(Response::HTTP_FORBIDDEN)
            ->assertJsonPath('message', 'User does not have the right roles.');
    }

    /**
     * @throws Exception
     */
    public function test_update_lender_settings_on_empty_default_order_cost_failed(): void
    {
        $this->actingAs(self::$admin)
            ->putJson(self::BaseUrl, Arr::except(self::$lenderSettingsData, ['default_order_cost']))
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonFragment([
                'message' => 'The default order cost field is required.',
                'errors' => [
                    'default_order_cost' => [
                        'The default order cost field is required.',
                    ],
                ],
            ]);
    }

    /**
     * @throws Exception
     */
    public function test_update_lender_settings_notify_admins_about_new_orders_field_is_required(): void
    {
        $this->actingAs(self::$admin)
            ->putJson(
                self::BaseUrl,
                Arr::except(
                    self::$lenderSettingsData,
                    ['notify_admins_about_new_orders']
                )
            )
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrorFor('notify_admins_about_new_orders');
    }

    /**
     * @throws Exception
     */
    public function test_update_lender_settings_on_invalid_default_order_cost_failed(): void
    {
        $this->actingAs(self::$admin)
            ->putJson(self::BaseUrl, array_merge(
                self::$lenderSettingsData,
                ['default_order_cost' => 'invalid']
            ))
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonFragment([
                'message' => 'The default order cost must be a number.',
                'errors' => [
                    'default_order_cost' => [
                        'The default order cost must be a number.',
                    ],
                ],
            ]);
    }

    /**
     * @throws Exception
     */
    public function test_update_lender_settings_on_empty_email_verification_enabled_failed(): void
    {
        $this->actingAs(self::$admin)
            ->putJson(self::BaseUrl, Arr::except(self::$lenderSettingsData, ['email_verification_enabled']))
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonFragment([
                'message' => 'The email verification enabled field is required.',
                'errors' => [
                    'email_verification_enabled' => [
                        'The email verification enabled field is required.',
                    ],
                ],
            ]);
    }

    /**
     * @throws Exception
     */
    public function test_update_lender_settings_on_invalid_email_verification_enabled_failed(): void
    {
        $this->actingAs(self::$admin)
            ->putJson(self::BaseUrl, array_merge(
                self::$lenderSettingsData,
                ['email_verification_enabled' => 'invalid']
            ))
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonFragment([
                'message' => 'The email verification enabled field must be true or false.',
                'errors' => [
                    'email_verification_enabled' => [
                        'The email verification enabled field must be true or false.',
                    ],
                ],
            ]);
    }

    /**
     * @throws Exception
     */
    public function test_update_lender_settings_on_empty_default_does_order_require_approval_failed(): void
    {
        $this->actingAs(self::$admin)
            ->putJson(self::BaseUrl, Arr::except(self::$lenderSettingsData, ['default_does_order_require_approval']))
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonFragment([
                'message' => 'The default does order require approval field is required.',
                'errors' => [
                    'default_does_order_require_approval' => [
                        'The default does order require approval field is required.',
                    ],
                ],
            ]);
    }

    /**
     * @throws Exception
     */
    public function test_update_lender_settings_on_invalid_default_does_order_require_approval_failed(): void
    {
        $this->actingAs(self::$admin)
            ->putJson(self::BaseUrl, array_merge(
                self::$lenderSettingsData,
                ['default_does_order_require_approval' => 'invalid']
            ))
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonFragment([
                'message' => 'The default does order require approval field must be true or false.',
                'errors' => [
                    'default_does_order_require_approval' => [
                        'The default does order require approval field must be true or false.',
                    ],
                ],
            ]);
    }

    /**
     * @throws Exception
     */
    public function test_update_lender_settings_on_empty_require_initiate_trade_request_failed(): void
    {
        $this->actingAs(self::$admin)
            ->putJson(self::BaseUrl, Arr::except(self::$lenderSettingsData, ['require_initiate_trade_request']))
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonFragment([
                'message' => 'The require initiate trade request field is required.',
                'errors' => [
                    'require_initiate_trade_request' => [
                        'The require initiate trade request field is required.',
                    ],
                ],
            ]);
    }

    /**
     * @throws Exception
     */
    public function test_update_lender_settings_on_empty_notify_borrowers_about_order_updates_failed(): void
    {
        $this->actingAs(self::$admin)
            ->putJson(self::BaseUrl, Arr::except(self::$lenderSettingsData, ['notify_borrowers_about_order_updates']))
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonFragment([
                'message' => 'The notify borrowers about order updates field is required.',
                'errors' => [
                    'notify_borrowers_about_order_updates' => [
                        'The notify borrowers about order updates field is required.',
                    ],
                ],
            ]);
    }

    /**
     * @throws Exception
     */
    public function test_update_lender_settings_on_empty_default_company_registration_status_failed(): void
    {
        $this->actingAs(self::$admin)
            ->putJson(self::BaseUrl, Arr::except(self::$lenderSettingsData, ['default_company_registration_status']))
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonFragment([
                'message' => 'The default company registration status field is required.',
                'errors' => [
                    'default_company_registration_status' => [
                        'The default company registration status field is required.',
                    ],
                ],
            ]);
    }

    /**
     * @throws Exception
     */
    public function test_update_lender_settings_on_invalid_and_not_integer_default_company_registration_status_failed(): void
    {
        $this->actingAs(self::$admin)
            ->putJson(self::BaseUrl, array_merge(
                self::$lenderSettingsData,
                ['default_company_registration_status' => 'invalid']
            ))
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonFragment([
                'message' => 'The default company registration status must be an integer. (and 1 more error)',
                'errors' => [
                    'default_company_registration_status' => [
                        'The default company registration status must be an integer.',
                        'The value you have entered is invalid.',
                    ],
                ],
            ]);
    }

    /**
     * @throws Exception
     */
    public function test_update_lender_settings_on_invalid_default_company_registration_status_failed(): void
    {
        $this->actingAs(self::$admin)
            ->putJson(self::BaseUrl, array_merge(
                self::$lenderSettingsData,
                ['default_company_registration_status' => 1000]
            ))
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonFragment([
                'message' => 'The value you have entered is invalid.',
                'errors' => [
                    'default_company_registration_status' => [
                        'The value you have entered is invalid.',
                    ],
                ],
            ]);
    }

    /**
     * @throws Exception
     */
    public function test_update_lender_settings_on_empty_default_company_status_created_by_operation_failed(): void
    {
        $this->actingAs(self::$admin)
            ->putJson(self::BaseUrl, Arr::except(self::$lenderSettingsData, ['default_company_status_created_by_operation']))
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonFragment([
                'message' => 'The default company status created by operation field is required.',
                'errors' => [
                    'default_company_status_created_by_operation' => [
                        'The default company status created by operation field is required.',
                    ],
                ],
            ]);
    }

    /**
     * @throws Exception
     */
    public function test_update_lender_settings_on_invalid_and_not_integer_default_company_status_created_by_operation_failed(): void
    {
        $this->actingAs(self::$admin)
            ->putJson(self::BaseUrl, array_merge(
                self::$lenderSettingsData,
                ['default_company_status_created_by_operation' => 'invalid']
            ))
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonFragment([
                'message' => 'The default company status created by operation must be an integer. (and 1 more error)',
                'errors' => [
                    'default_company_status_created_by_operation' => [
                        'The default company status created by operation must be an integer.',
                        'The value you have entered is invalid.',
                    ],
                ],
            ]);
    }

    /**
     * @throws Exception
     */
    public function test_update_lender_settings_on_invalid_default_company_status_created_by_operation_failed(): void
    {
        $this->actingAs(self::$admin)
            ->putJson(self::BaseUrl, array_merge(
                self::$lenderSettingsData,
                ['default_company_status_created_by_operation' => 1000]
            ))
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonFragment([
                'message' => 'The value you have entered is invalid.',
                'errors' => [
                    'default_company_status_created_by_operation' => [
                        'The value you have entered is invalid.',
                    ],
                ],
            ]);
    }

    /**
     * @throws Exception
     */
    public function test_that_auth_user_has_admin_role_can_update_lender_settings_succeed(): void
    {
        $this->actingAs(self::$admin)
            ->putJson(self::BaseUrl, self::$lenderSettingsData)
            ->assertStatus(Response::HTTP_OK)
            ->assertJsonStructure([
                'data',
            ]);
    }

    /**
     * @throws Exception
     */
    public function test_that_auth_user_has_manager_role_and_right_permission_can_update_lender_settings_succeed(): void
    {
        $this->actingAs(self::$managerHasPermission)
            ->putJson(self::BaseUrl, self::$lenderSettingsData)
            ->assertStatus(Response::HTTP_OK)
            ->assertJsonStructure([
                'data',
            ]);
    }

    public function test_that_auth_user_without_right_permissions_cannot_update_lender_settings_failed(): void
    {
        $this->actingAs(self::$manager)
            ->putJson(self::BaseUrl, self::$lenderSettingsData)
            ->assertStatus(Response::HTTP_FORBIDDEN)
            ->assertJsonPath('message', 'User does not have the right permissions.');
    }
}
