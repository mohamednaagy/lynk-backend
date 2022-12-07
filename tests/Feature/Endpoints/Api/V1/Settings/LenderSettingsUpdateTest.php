<?php

namespace Tests\Feature\Endpoints\Api\V1\Settings;

use App\Enums\CompanyStatus;
use App\Enums\Role;
use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\Fluent\AssertableJson;
use Modules\Grantify\Facades\Grantify;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;
use Tests\Traits\InteractsWithLender;

class LenderSettingsUpdateTest extends TestCase
{
    use RefreshDatabase, InteractsWithLender;

    const BaseUrl = 'api/v1/lender/settings';

    private static Company $company;

    private static User $userLender;

    private static array $updatedLenderSettingsDetails;

    /**
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        [self::$company] = $this->createCompany('2000', ['company_cr' => '12345678910']);
        self::$userLender = $this->createLenderUser(self::$company->id, Role::LenderAdmin, 'lenderAdmin@bim.com');
        self::$updatedLenderSettingsDetails = [
            'does_order_require_approval' => true,
        ];
    }

    /**
     * @return void
     */
    public function test_that_un_auth_user_cant_index_lender_settings(): void
    {
        $this->withHeader('X-Company', self::$company->id)
            ->putJson(self::BaseUrl)
            ->assertStatus(Response::HTTP_UNAUTHORIZED)
            ->assertExactJson([
                'message' => 'Unauthenticated.',
            ]);
    }

    /**
     * @return void
     */
    public function test_update_lender_settings_on_empty_does_order_require_approval(): void
    {
        $this->actingAs(self::$userLender)
            ->withHeader('X-Company', self::$company->id)
            ->putJson(self::BaseUrl, [])
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertExactJson([
                'message' => 'The does order require approval field is required.',
                'errors' => [
                    'does_order_require_approval' => [
                        'The does order require approval field is required.',
                    ],
                ],
            ]);
    }

    /**
     * @return void
     */
    public function test_that_auth_user_has_lender_admin_role_can_update_lender_settings(): void
    {
        $this->actingAs(self::$userLender)
            ->withHeader('X-Company', self::$company->id)
            ->putJson(self::BaseUrl, self::$updatedLenderSettingsDetails)
            ->assertStatus(Response::HTTP_OK);

        $this->assertEquals(
            Company::find(self::$company->id)->does_order_require_approval,
            self::$updatedLenderSettingsDetails['does_order_require_approval']
        );
    }

    /**
     * @return void
     */
    public function test_that_auth_user_has_lender_api_user_role_can_update_lender_settings(): void
    {
        Grantify::syncRoleToModel(self::$userLender, Role::LenderApiUser);

        $this->actingAs(self::$userLender)
            ->withHeader('X-Company', self::$company->id)
            ->putJson(self::BaseUrl, self::$updatedLenderSettingsDetails)
            ->assertStatus(Response::HTTP_OK);

        $this->assertEquals(
            Company::find(self::$company->id)->does_order_require_approval,
            self::$updatedLenderSettingsDetails['does_order_require_approval']
        );
    }

    /**
     * @return void
     */
    public function test_that_auth_user_has_lender_supervisor_role_cannot_index_lender_settings(): void
    {
        Grantify::syncRoleToModel(self::$userLender, Role::LenderSupervisor);

        $this->actingAs(self::$userLender)
            ->withHeader('X-Company', self::$company->id)
            ->putJson(self::BaseUrl, self::$updatedLenderSettingsDetails)
            ->assertStatus(Response::HTTP_FORBIDDEN)
            ->assertJson(
                fn (AssertableJson $json) => $json->where('message', 'User does not have the right permissions.')
                    ->etc()
            );
    }

    /**
     * @return void
     */
    public function test_that_auth_user_has_lender_belling_role_cannot_index_lender_settings(): void
    {
        Grantify::syncRoleToModel(self::$userLender, Role::LenderBilling);

        $this->actingAs(self::$userLender)
            ->withHeader('X-Company', self::$company->id)
            ->putJson(self::BaseUrl, self::$updatedLenderSettingsDetails)
            ->assertStatus(Response::HTTP_FORBIDDEN)
            ->assertJson(
                fn (AssertableJson $json) => $json->where('message', 'User does not have the right permissions.')
                    ->etc()
            );
    }

    /**
     * @return void
     */
    public function test_that_auth_user_has_lender_creator_role_cannot_index_lender_settings(): void
    {
        Grantify::syncRoleToModel(self::$userLender, Role::LenderOrderCreator);

        $this->actingAs(self::$userLender)
            ->withHeader('X-Company', self::$company->id)
            ->putJson(self::BaseUrl, self::$updatedLenderSettingsDetails)
            ->assertStatus(Response::HTTP_FORBIDDEN)
            ->assertJson(
                fn (AssertableJson $json) => $json->where('message', 'User does not have the right permissions.')
                    ->etc()
            );
    }

    /**
     * @return void
     */
    public function test_that_unauthorized_user_with_not_verified_email_cannot_index_lender_settings(): void
    {
        // update user email verified at to be null
        self::$userLender->email_verified_at = null;
        self::$userLender->save();

        $this->actingAs(self::$userLender)
            ->withHeader('X-Company', self::$company->id)
            ->putJson(self::BaseUrl, self::$updatedLenderSettingsDetails)
            ->assertStatus(Response::HTTP_FORBIDDEN)
            ->assertJson(
                fn (AssertableJson $json) => $json->where('message', __('error.must_verify_email'))
                    ->where('code', 1008)
            );
    }

    /**
     * @return void
     */
    public function test_that_unauthorized_user_when_company_not_active_cannot_index_lender_settings(): void
    {
        // update user email verified at to be null
        self::$company->status = CompanyStatus::Pending;
        self::$company->save();

        $this->actingAs(self::$userLender)
            ->withHeader('X-Company', self::$company->id)
            ->putJson(self::BaseUrl, self::$updatedLenderSettingsDetails)
            ->assertStatus(Response::HTTP_FORBIDDEN)
            ->assertJson(
                fn (AssertableJson $json) => $json->where('message', __('error.company_not_active'))
                    ->where('code', 1015)
            );
    }
}
