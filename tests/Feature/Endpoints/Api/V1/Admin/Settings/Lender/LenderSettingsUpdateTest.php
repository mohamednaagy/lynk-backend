<?php

namespace Tests\Feature\Endpoints\Api\V1\Admin\Settings\Lender;

use App\Enums\Action;
use App\Enums\Area;
use App\Enums\CompanyStatus;
use App\Enums\Subject;
use App\Models\User;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\Fluent\AssertableJson;
use Modules\Grantify\Facades\Grantify;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;
use Tests\Traits\InteractsWithAdmin;
use Tests\Traits\InteractsWithSettings;

class LenderSettingsUpdateTest extends TestCase
{
    use RefreshDatabase, InteractsWithAdmin, InteractsWithSettings;

    const BaseUrl = 'api/v1/admin/settings/lender';

    private static User $admin;

    private static User $manager;

    private static $lenderSettings;

    private static array $lenderSettingsData = [];

    /**
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        self::$admin = $this->createAdmin();
        self::$manager = $this->createManager(permissions: perm(Area::SuperAdmin, [Subject::LenderAreaSettings, Action::Manage]));
        self::$lenderSettings = $this->getSettingsClass(Area::Lender);
        self::$lenderSettingsData = [
            'default_order_cost' => 150,
            'email_verification_enabled' => true,
            'default_does_order_require_approval' => false,
            'default_company_registration_status' => CompanyStatus::UnderReview,
            'default_company_status_created_by_operation' => CompanyStatus::UnderReview,
        ];
    }

    /**
     * @return void
     */
    public function test_that_un_auth_user_cant_index_lender_settings(): void
    {
        $this->putJson(self::BaseUrl)
            ->assertStatus(Response::HTTP_UNAUTHORIZED)
            ->assertExactJson([
                'message' => 'Unauthenticated.',
            ]);
    }

    /**
     * @return void
     *
     * @throws Exception
     */
    public function test_update_lender_settings_on_empty_default_order_cost(): void
    {
        unset(self::$lenderSettingsData['default_order_cost']);

        $this->actingAs(self::$admin)
            ->putJson(self::BaseUrl, self::$lenderSettingsData)
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
     * @return void
     *
     * @throws Exception
     */
    public function test_update_lender_settings_on_invalid_default_order_cost(): void
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
     * @return void
     *
     * @throws Exception
     */
    public function test_update_lender_settings_on_empty_email_verification_enabled(): void
    {
        unset(self::$lenderSettingsData['email_verification_enabled']);

        $this->actingAs(self::$admin)
            ->putJson(self::BaseUrl, self::$lenderSettingsData)
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
     * @return void
     *
     * @throws Exception
     */
    public function test_update_lender_settings_on_invalid_email_verification_enabled(): void
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
     * @return void
     *
     * @throws Exception
     */
    public function test_update_lender_settings_on_empty_default_does_order_require_approval(): void
    {
        unset(self::$lenderSettingsData['default_does_order_require_approval']);

        $this->actingAs(self::$admin)
            ->putJson(self::BaseUrl, self::$lenderSettingsData)
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
     * @return void
     *
     * @throws Exception
     */
    public function test_update_lender_settings_on_invalid_default_does_order_require_approval(): void
    {
        unset(self::$lenderSettingsData['default_does_order_require_approval']);

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
     * @return void
     *
     * @throws Exception
     */
    public function test_update_lender_settings_on_empty_default_company_registration_status(): void
    {
        unset(self::$lenderSettingsData['default_company_registration_status']);

        $this->actingAs(self::$admin)
            ->putJson(self::BaseUrl, self::$lenderSettingsData)
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
     * @return void
     *
     * @throws Exception
     */
    public function test_update_lender_settings_on_invalid_and_not_integer_default_company_registration_status(): void
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
     * @return void
     *
     * @throws Exception
     */
    public function test_update_lender_settings_on_invalid_default_company_registration_status(): void
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
     * @return void
     *
     * @throws Exception
     */
    public function test_update_lender_settings_on_empty_default_company_status_created_by_operation(): void
    {
        unset(self::$lenderSettingsData['default_company_status_created_by_operation']);

        $this->actingAs(self::$admin)
            ->putJson(self::BaseUrl, self::$lenderSettingsData)
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
     * @return void
     *
     * @throws Exception
     */
    public function test_update_lender_settings_on_invalid_and_not_integer_default_company_status_created_by_operation(): void
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
     * @return void
     *
     * @throws Exception
     */
    public function test_update_lender_settings_on_invalid_default_company_status_created_by_operation(): void
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
     * @return void
     *
     * @throws Exception
     */
    public function test_that_auth_user_has_admin_role_can_update_lender_settings(): void
    {
        $this->actingAs(self::$admin)
            ->putJson(self::BaseUrl, self::$lenderSettingsData)
            ->assertStatus(Response::HTTP_OK)
            ->assertJsonStructure([
                'data',
            ]);
    }

    /**
     * @return void
     *
     * @throws Exception
     */
    public function test_that_auth_user_has_manager_role_can_update_lender_settings(): void
    {
        $this->actingAs(self::$manager)
            ->putJson(self::BaseUrl, self::$lenderSettingsData)
            ->assertStatus(Response::HTTP_OK)
            ->assertJsonStructure([
                'data',
            ]
            );
    }

    /**
     * @return void
     */
    public function test_that_auth_user_without_right_permissions_cannot_update_lender_settings(): void
    {
        Grantify::syncPermissionToModel(self::$manager, []);

        $this->actingAs(self::$manager)
            ->putJson(self::BaseUrl, self::$lenderSettingsData)
            ->assertStatus(Response::HTTP_FORBIDDEN)
            ->assertJson(
                fn (AssertableJson $json) => $json->where('message', 'User does not have the right permissions.')
                    ->etc()
            );
    }
}
