<?php

namespace Tests\Feature\Endpoints\Api\V1\Lender\Settings;

use App\Enums\CompanyStatus;
use App\Enums\Role;
use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\Fluent\AssertableJson;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;
use Tests\Traits\InteractsWithCompany;
use Tests\Traits\InteractsWithUser;

class LenderSettingsUpdateTest extends TestCase
{
    use InteractsWithCompany, InteractsWithUser, RefreshDatabase;

    const BaseUrl = 'api/v1/lender/settings';

    private static Company $company;

    private static User $userLender;

    private static User $userLenderApiUser;

    private static User $userLenderSupervisor;

    private static User $userLenderBilling;

    private static User $userLenderOrderCreator;

    private static array $updatedLenderSettingsDetails;

    public function setUp(): void
    {
        parent::setUp();

        [self::$company] = $this->createCompany('2000', ['company_cr' => '12345678910']);
        self::$userLender = $this->createLenderUser(self::$company->id, Role::LenderAdmin);
        self::$userLenderApiUser = $this->createLenderUser(self::$company->id, Role::LenderApiUser);
        self::$userLenderSupervisor = $this->createLenderUser(self::$company->id, Role::LenderSupervisor);
        self::$userLenderBilling = $this->createLenderUser(self::$company->id, Role::LenderBilling);
        self::$userLenderOrderCreator = $this->createLenderUser(self::$company->id, Role::LenderOrderCreator);
        self::$updatedLenderSettingsDetails = [
            'does_order_require_approval' => true,
            'force_unique_reference_number' => true,
            'notify_borrowers_about_order_updates' => true,
            'require_initiate_trade_request' => true,
        ];
    }

    public function test_that_un_auth_user_cant_index_lender_settings(): void
    {
        $this->withHeader('X-Company', self::$company->id)
            ->putJson(self::BaseUrl)
            ->assertStatus(Response::HTTP_UNAUTHORIZED)
            ->assertExactJson([
                'message' => 'Unauthenticated.',
            ]);
    }

    public function test_update_lender_settings_on_empty_does_order_require_approval_fails(): void
    {
        $this->actingAs(self::$userLender)
            ->withHeader('X-Company', self::$company->id)
            ->putJson(self::BaseUrl, \Arr::except(self::$updatedLenderSettingsDetails, 'does_order_require_approval'))
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrorFor('does_order_require_approval');
    }

    public function test_update_lender_settings_on_empty_force_unique_reference_number_fails(): void
    {
        $this->actingAs(self::$userLender)
            ->withHeader('X-Company', self::$company->id)
            ->putJson(self::BaseUrl, \Arr::except(self::$updatedLenderSettingsDetails, 'force_unique_reference_number'))
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrorFor('force_unique_reference_number');
    }

    public function test_update_lender_settings_on_empty_notify_borrowers_about_order_updates_fails(): void
    {
        $this->actingAs(self::$userLender)
            ->withHeader('X-Company', self::$company->id)
            ->putJson(self::BaseUrl, \Arr::except(self::$updatedLenderSettingsDetails, 'notify_borrowers_about_order_updates'))
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrorFor('notify_borrowers_about_order_updates');
    }

    public function test_update_lender_settings_on_empty_require_initiate_trade_request_fails(): void
    {
        $this->actingAs(self::$userLender)
            ->withHeader('X-Company', self::$company->id)
            ->putJson(self::BaseUrl, \Arr::except(self::$updatedLenderSettingsDetails, 'require_initiate_trade_request'))
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrorFor('require_initiate_trade_request');
    }

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

        $this->assertEquals(
            Company::find(self::$company->id)->force_unique_reference_number,
            self::$updatedLenderSettingsDetails['force_unique_reference_number']
        );

        $this->assertEquals(
            Company::find(self::$company->id)->notify_borrowers_about_order_updates,
            self::$updatedLenderSettingsDetails['notify_borrowers_about_order_updates']
        );

        $this->assertEquals(
            Company::find(self::$company->id)->require_initiate_trade_request,
            self::$updatedLenderSettingsDetails['require_initiate_trade_request']
        );
    }

    public function test_that_auth_user_has_lender_api_user_role_can_update_lender_settings(): void
    {
        $this->actingAs(self::$userLenderApiUser)
            ->withHeader('X-Company', self::$company->id)
            ->putJson(self::BaseUrl, self::$updatedLenderSettingsDetails)
            ->assertOk();

        $this->assertEquals(
            Company::find(self::$company->id)->does_order_require_approval,
            self::$updatedLenderSettingsDetails['does_order_require_approval']
        );

        $this->assertEquals(
            Company::find(self::$company->id)->notify_borrowers_about_order_updates,
            self::$updatedLenderSettingsDetails['notify_borrowers_about_order_updates']
        );

        $this->assertEquals(
            Company::find(self::$company->id)->require_initiate_trade_request,
            self::$updatedLenderSettingsDetails['require_initiate_trade_request']
        );
    }

    public function test_that_auth_user_has_lender_supervisor_role_cannot_update_lender_settings(): void
    {
        $this->actingAs(self::$userLenderSupervisor)
            ->withHeader('X-Company', self::$company->id)
            ->putJson(self::BaseUrl, self::$updatedLenderSettingsDetails)
            ->assertStatus(Response::HTTP_FORBIDDEN)
            ->assertJson(
                fn (AssertableJson $json) => $json->where('message', __('User does not have the right permissions.'))
                    ->etc()
            );
    }

    public function test_that_auth_user_has_lender_billing_role_cannot_update_lender_settings(): void
    {
        $this->actingAs(self::$userLenderBilling)
            ->withHeader('X-Company', self::$company->id)
            ->putJson(self::BaseUrl, self::$updatedLenderSettingsDetails)
            ->assertStatus(Response::HTTP_FORBIDDEN)
            ->assertJson(
                fn (AssertableJson $json) => $json->where('message', __('User does not have the right permissions.'))
                    ->etc()
            );
    }

    public function test_that_auth_user_has_lender_creator_role_cannot_update_lender_settings(): void
    {
        $this->actingAs(self::$userLenderOrderCreator)
            ->withHeader('X-Company', self::$company->id)
            ->putJson(self::BaseUrl, self::$updatedLenderSettingsDetails)
            ->assertStatus(Response::HTTP_FORBIDDEN)
            ->assertJson(
                fn (AssertableJson $json) => $json->where('message', __('User does not have the right permissions.'))
                    ->etc()
            );
    }

    public function test_that_user_with_not_verified_email_cannot_update_lender_settings(): void
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

    public function test_that_user_when_company_not_active_cannot_update_lender_settings(): void
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
