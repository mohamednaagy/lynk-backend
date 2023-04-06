<?php

namespace Tests\Feature\Endpoints\Api\V1\Lender\Webhooks;

use App\Enums\CompanyStatus;
use App\Enums\ErrorCode;
use App\Enums\Role;
use App\Models\Company;
use App\Models\User;
use App\Models\Webhook;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\InteractsWithCompany;
use Tests\Traits\InteractsWithUser;

class WebhookControllerDestroyTest extends TestCase
{
    use RefreshDatabase;
    use InteractsWithUser;
    use InteractsWithCompany;

    private static Company $company;

    private static Company $companyNotActivated;

    private static User $userLenderAdmin;

    private static User $lenerAdminNotVerified;

    private static User $userLenderAdminBelongToCompanyNotActivated;

    private static User $lenderBilling;

    private static User $lenderCreator;

    private static User $lenderSuperVisor;

    private static User $lenderApiUser;

    private static string $endpoint;

    private static Webhook $webhook;

    private static Webhook $anotherWebhook;

    /**
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        [self::$company] = $this->createCompany(
            '2000',
            [
                'company_cr' => '12345678910',
            ]
        );

        [self::$companyNotActivated] = $this->createCompany('2000', [
            'company_cr' => '12345678999',
            'status' => CompanyStatus::Pending,
        ]);

        self::$lenerAdminNotVerified = $this->createLenderUser(
            self::$company->id,
            Role::LenderAdmin,
            [
                'email_verified_at' => null,
            ]
        );

        self::$userLenderAdminBelongToCompanyNotActivated = $this->createLenderUser(self::$companyNotActivated->id, Role::LenderAdmin);
        self::$userLenderAdmin = $this->createLenderUser(self::$company->id, Role::LenderAdmin);
        self::$lenderBilling = $this->createLenderUser(self::$company->id, Role::LenderBilling);
        self::$lenderCreator = $this->createLenderUser(self::$company->id, Role::LenderOrderCreator);
        self::$lenderSuperVisor = $this->createLenderUser(self::$company->id, Role::LenderSupervisor);
        self::$lenderApiUser = $this->createLenderUser(self::$company->id, Role::LenderApiUser);
        self::$webhook = Webhook::factory()->create(['company_id' => self::$company->id]);
        self::$anotherWebhook = Webhook::factory()->create(['company_id' => self::$companyNotActivated->id]);
        self::$endpoint = '/api/v1/lender/webhooks/';
    }

    public function test_unauth_user_cant_delete_webhook_unsuccessfully()
    {
        $this->withHeader('X-Company', self::$company->id)
            ->deleteJson(self::$endpoint.self::$webhook->id)
            ->assertExactJson([
                'message' => __('Unauthenticated.'),
            ]);
    }

    public function test_lender_admin_can_delete_webhook_successfully()
    {
        $webhookCount = Webhook::count();
        $this->actingAs(self::$userLenderAdmin)
            ->withHeader('X-Company', self::$company->id)
            ->deleteJson(self::$endpoint.self::$webhook->id)
            ->assertStatus(200)
            ->assertExactJson([
                'data' => [],
            ]);
        $this->assertEquals(1, $webhookCount - 1);
    }

    public function test_lender_admin_not_verify_email_cant_delete_webhook_unsuccessfully()
    {
        $this->actingAs(self::$lenerAdminNotVerified)
            ->withHeader('X-Company', self::$company->id)
            ->deleteJson(self::$endpoint.self::$webhook->id)
            ->assertStatus(403)
            ->assertJsonFragment([
                'message' => __('error.must_verify_email'),
                'code' => ErrorCode::EMAIL_NOT_VERIFIED,
            ]);
    }

    public function test_lender_admin_cant_delete_webhook_when_company_not_active_unsuccessfully()
    {
        $this->actingAs(self::$userLenderAdminBelongToCompanyNotActivated)
            ->withHeader('X-Company', self::$companyNotActivated->id)
            ->deleteJson(self::$endpoint.self::$anotherWebhook->id)
            ->assertStatus(403)
            ->assertJsonFragment([
                'message' => __('error.company_not_active'),
                'code' => ErrorCode::COMPANY_NOT_ACTIVE,
            ]);
    }

    public function test_lender_billing_cant_delete_webhook_unsuccessfully()
    {
        $this->actingAs(self::$lenderBilling)
            ->withHeader('X-Company', self::$company->id)
            ->deleteJson(self::$endpoint.self::$webhook->id)
            ->assertStatus(403);
    }

    public function test_lender_order_creator_cant_delete_webhook_unsuccessfully()
    {
        $this->actingAs(self::$lenderCreator)
            ->withHeader('X-Company', self::$company->id)
            ->deleteJson(self::$endpoint.self::$webhook->id)
            ->assertStatus(403);
    }

    public function test_lender_supervisor_cant_delete_webhook_unsuccessfully()
    {
        $this->actingAs(self::$lenderSuperVisor)
            ->withHeader('X-Company', self::$company->id)
            ->deleteJson(self::$endpoint.self::$webhook->id)
            ->assertStatus(403);
    }

    public function test_lender_api_user_can_delete_webhook_successfully()
    {
        $this->actingAs(self::$lenderApiUser)
            ->withHeader('X-Company', self::$company->id)
            ->deleteJson(self::$endpoint.self::$webhook->id)
            ->assertStatus(200);
    }
}
