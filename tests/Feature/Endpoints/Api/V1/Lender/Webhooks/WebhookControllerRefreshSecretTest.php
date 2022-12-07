<?php

namespace Tests\Feature\Endpoints\Api\V1\Lender\Webhooks;

use App\Enums\CompanyStatus;
use App\Enums\ErrorCode;
use App\Enums\Role;
use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Tests\TestCase;
use Tests\Traits\InteractsWithLender;

class WebhookControllerRefreshSecretTest extends TestCase
{
    use RefreshDatabase;
    use InteractsWithLender;

    private static Company $company;

    private static Company $companyNotActivated;

    private static User $userLenderAdmin;

    private static User $lenerAdminNotVerified;

    private static User $userLenderAdminBelongToCompanyNotActivated;

    private static User $lenderBilling;

    private static User $lenderCreator;

    private static User $lenderSuperVisor;

    private static User $lenderApiUser;

    /**
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        self::$company = $this->createCompany(
            '2000',
            [
                'company_cr' => '12345678910',
                'webhook_secret_key' => 'secret_key',
            ]
        )[0];

        self::$companyNotActivated = $this->createCompany('2000', [
            'company_cr' => '12345678999',
            'status' => CompanyStatus::Pending,
        ])[0];

        self::$userLenderAdminBelongToCompanyNotActivated = $this->createLenderUser(self::$companyNotActivated->id, Role::LenderAdmin, 'lenderAdmin1@bim.com');
        self::$userLenderAdmin = $this->createLenderUser(self::$company->id, Role::LenderAdmin, 'lenderAdmin@bim.com');
        self::$lenderBilling = $this->createLenderUser(self::$company->id, Role::LenderBilling, 'LenderBilling@bim.com');
        self::$lenderCreator = $this->createLenderUser(self::$company->id, Role::LenderOrderCreator, 'LenderOrderCreator@bim.com');
        self::$lenderSuperVisor = $this->createLenderUser(self::$company->id, Role::LenderSupervisor, 'LenderSupervisor@bim.com');
        self::$lenderApiUser = $this->createLenderUser(self::$company->id, Role::LenderApiUser, 'LenderSupervisor@bim.com');
        self::$lenerAdminNotVerified = $this->createLenderUser(
            self::$company->id,
            Role::LenderAdmin,
            'lenderAdmin2@bim.com',
            [
                'email_verified_at' => null,
            ]
        );
    }

    public function test_webhook_controller_refresh_secret_successed()
    {
        $this->actingAs(self::$userLenderAdmin)
            ->withHeader('X-Company', self::$company->id)
            ->putJson('/api/v1/lender/webhooks/refresh-secret')
            ->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'webhook_secret_key',
                ],
            ]);
    }

    public function test_webhook_controller_refresh_secret_key_is_encrypted()
    {
        $this->actingAs(self::$userLenderAdmin)
            ->withHeader('X-Company', self::$company->id)
            ->putJson('/api/v1/lender/webhooks/refresh-secret');

        $secretKeyFromDatabaseWitoutCasts = Company::find(self::$company->id)->getRawOriginal('webhook_secret_key');

        // can not decrypt string that not enypted
        $this->assertIsString(
            Crypt::decryptString($secretKeyFromDatabaseWitoutCasts)
        );
    }

    public function test_webhook_controller_refresh_secret_lender_can_not_access_without_verify_email()
    {
        $this->withHeader('X-Company', self::$company->id)
            ->actingAs(self::$lenerAdminNotVerified)
            ->putJson('/api/v1/lender/webhooks/refresh-secret')
            ->assertStatus(403)
            ->assertJsonFragment([
                'message' => __('error.must_verify_email'),
                'code' => ErrorCode::EMAIL_NOT_VERIFIED,
            ]);
    }

     public function test_webhook_controller_refresh_secret_lender_can_not_access_when_company_not_active()
     {
         $this->withHeader('X-Company', self::$companyNotActivated->id)
             ->actingAs(self::$userLenderAdminBelongToCompanyNotActivated)
             ->putJson('/api/v1/lender/webhooks/refresh-secret')
             ->assertStatus(403)->assertJsonFragment([
                 'message' => __('error.company_not_active'),
                 'code' => ErrorCode::COMPANY_NOT_ACTIVE,
             ]);
     }

    public function test_webhook_controller_refresh_secret_lender_billing_can_not_access()
    {
        $this->withHeader('X-Company', self::$company->id)
            ->actingAs(self::$lenderBilling)
            ->putJson('/api/v1/lender/webhooks/refresh-secret')
            ->assertStatus(403);
    }

    public function test_webhook_controller_refresh_secret_lender_creator_can_not_access()
    {
        $this->withHeader('X-Company', self::$company->id)
            ->actingAs(self::$lenderCreator)
            ->putJson('/api/v1/lender/webhooks/refresh-secret')
            ->assertStatus(403);
    }

    public function test_webhook_controller_refresh_secret_lender_supervisor_can_not_access()
    {
        $this->withHeader('X-Company', self::$company->id)
            ->actingAs(self::$lenderSuperVisor)
            ->putJson('/api/v1/lender/webhooks/refresh-secret')
            ->assertStatus(403);
    }

    public function test_webhook_controller_refresh_secret_lender_api_user_can_access()
    {
        $this->withHeader('X-Company', self::$company->id)
            ->actingAs(self::$lenderApiUser)
            ->putJson('/api/v1/lender/webhooks/refresh-secret')
            ->assertStatus(200);
    }

    public function test_webhook_controller_refresh_secret_lender_admin_can_access()
    {
        $this->withHeader('X-Company', self::$company->id)
            ->actingAs(self::$userLenderAdmin)
            ->putJson('/api/v1/lender/webhooks/refresh-secret')
            ->assertStatus(200);
    }
}
