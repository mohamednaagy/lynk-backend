<?php

namespace Tests\Feature\Endpoints\Api\V1\Lender\Webhooks;

use App\Enums\CompanyStatus;
use App\Enums\ErrorCode;
use App\Enums\Role;
use App\Enums\WebhookType;
use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\InteractsWithLender;

class WebhookControllerStoreTest extends TestCase
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

    private static string $url;

    /**
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        self::$url = 'http://localhost.com';
        self::$company = $this->createCompany('2000', ['company_cr' => '12345678910'])[0];
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

    public function test_webhook_controller_store_success()
    {
        $this->actingAs(self::$userLenderAdmin)
            ->withHeader('X-Company', self::$company->id)
            ->postJson('/api/v1/lender/webhooks', [
                'url' => self::$url,
                'type' => WebhookType::OrderUpdates,
            ])
            ->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'url',
                    'type' => [
                        'description',
                        'value',
                    ], ],
            ]);
    }

    public function test_webhook_controller_store_invalid_url()
    {
        $this->actingAs(self::$userLenderAdmin)
            ->withHeader('X-Company', self::$company->id)
            ->postJson('/api/v1/lender/webhooks', [
                'url' => 'invaild url',
                'type' => WebhookType::OrderUpdates,
            ])
            ->assertStatus(422)
            ->assertJsonFragment([
                'message' => 'The url must be a valid URL.',
                'errors' => [
                    'url' => [
                        'The url must be a valid URL.',
                    ],
                ],
            ]);
    }

    public function test_webhook_controller_store_invalid_type()
    {
        $this->actingAs(self::$userLenderAdmin)
            ->withHeader('X-Company', self::$company->id)
            ->postJson('/api/v1/lender/webhooks', [
                'url' => self::$url,
                'type' => 'invalid type',
            ])
            ->assertJsonFragment([
                'message' => __('error.webhook_type_not_supported'),
                'code' => ErrorCode::WEBHOOK_LIMIT_TYPE_NOT_FOUND,
            ]);
    }

    public function test_webhook_controller_store_require_fields()
    {
        $this->actingAs(self::$userLenderAdmin)
            ->withHeader('X-Company', self::$company->id)
            ->postJson('/api/v1/lender/webhooks')
            ->assertStatus(422)
            ->assertJsonFragment([
                'message' => 'The url field is required. (and 1 more error)',
                'errors' => [
                    'url' => [
                        'The url field is required.',
                    ],
                    'type' => [
                        'The type field is required.',
                    ],
                ],
            ]);
    }

    public function test_webhook_controller_store_webhook_count_can_not_be_more_the_limitation()
    {
        $limit = 0;
        config()->set('webhook-server.limits.'.WebhookType::OrderUpdates, $limit);

        $this->actingAs(self::$userLenderAdmin)
            ->withHeader('X-Company', self::$company->id)
            ->postJson('/api/v1/lender/webhooks', [
                'url' => self::$url,
                'type' => WebhookType::OrderUpdates,
            ])
            ->assertStatus(422)->assertJsonFragment([
            'message' => __('validation.webhook_type_limit', ['limit' => $limit]),
        ]);
    }

    public function test_webhook_controller_store_lender_can_not_access_without_verify_email()
    {
        $this->withHeader('X-Company', self::$company->id)
            ->actingAs(self::$lenerAdminNotVerified)
            ->postJson('/api/v1/lender/webhooks', [
                'url' => self::$url,
                'type' => WebhookType::OrderUpdates,
            ])
            ->assertStatus(403)
            ->assertJsonFragment([
                'message' => __('error.must_verify_email'),
                'code' => ErrorCode::EMAIL_NOT_VERIFIED,
            ]);
    }

     public function test_webhook_controller_store_lender_can_not_access_when_company_not_active()
     {
         $this->withHeader('X-Company', self::$companyNotActivated->id)
             ->actingAs(self::$userLenderAdminBelongToCompanyNotActivated)
             ->postJson('/api/v1/lender/webhooks', [
                 'url' => self::$url,
                 'type' => WebhookType::OrderUpdates,
             ])
             ->assertStatus(403)->assertJsonFragment([
                'message' => __('error.company_not_active'),
                'code' => ErrorCode::COMPANY_NOT_ACTIVE,
            ]);
     }

    public function test_webhook_controller_store_lender_admin_can_access()
    {
        $this->withHeader('X-Company', self::$company->id)
            ->actingAs(self::$userLenderAdmin)
            ->postJson('/api/v1/lender/webhooks', [
                'url' => self::$url,
                'type' => WebhookType::OrderUpdates,
            ])
            ->assertStatus(200);
    }

    public function test_webhook_controller_store_lender_billing_can_access()
    {
        $this->withHeader('X-Company', self::$company->id)
            ->actingAs(self::$lenderBilling)
            ->postJson('/api/v1/lender/webhooks', [
                'url' => self::$url,
                'type' => WebhookType::OrderUpdates,
            ])
            ->assertStatus(200);
    }

    public function test_webhook_controller_store_lender_creator_can_access()
    {
        $this->withHeader('X-Company', self::$company->id)
            ->actingAs(self::$lenderCreator)
            ->postJson('/api/v1/lender/webhooks', [
                'url' => self::$url,
                'type' => WebhookType::OrderUpdates,
            ])
            ->assertStatus(200);
    }

    public function test_webhook_controller_store_lender_supervisor_can_access()
    {
        $this->withHeader('X-Company', self::$company->id)
            ->actingAs(self::$lenderSuperVisor)
            ->postJson('/api/v1/lender/webhooks', [
                'url' => self::$url,
                'type' => WebhookType::OrderUpdates,
            ])
            ->assertStatus(200);
    }

    public function test_webhook_controller_store_lender_api_user_can_access()
    {
        $this->withHeader('X-Company', self::$company->id)
            ->actingAs(self::$lenderApiUser)
            ->postJson('/api/v1/lender/webhooks', [
                'url' => self::$url,
                'type' => WebhookType::OrderUpdates,
            ])
            ->assertStatus(200);
    }
}
