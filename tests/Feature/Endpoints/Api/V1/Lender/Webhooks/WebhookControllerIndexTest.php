<?php

namespace Tests\Feature\Endpoints\Api\V1\Lender\Webhooks;

use App\Enums\CompanyStatus;
use App\Enums\ErrorCode;
use App\Enums\Role;
use App\Models\Company;
use App\Models\User;
use App\Models\Webhook;
use App\Transformers\WebhookTransformer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\InteractsWithCompany;
use Tests\Traits\InteractsWithUser;

class WebhookControllerIndexTest extends TestCase
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

        self::$endpoint = '/api/v1/lender/webhooks';
    }

    public function test_lender_admin_can_index_webhooks_successfully()
    {
        $webhooks = Webhook::where('company_id', self::$company->id)
            ->paginate();
        $this->actingAs(self::$userLenderAdmin)
            ->withHeader('X-Company', self::$company->id)
            ->getJson(self::$endpoint)
            ->assertStatus(200)
            ->assertExactJson(
                fractal($webhooks, new WebhookTransformer())
                    ->parseIncludes([
                        'id',
                        'subject',
                        'status',
                        'creation_date',
                    ])
                    ->respond()->getData(true)
            );
    }

    public function test_unauth_user_cant_index_webhooks_unsuccessfully()
    {
        $this->withHeader('X-Company', self::$company->id)
            ->getJson(self::$endpoint)
            ->assertExactJson([
                'message' => __('Unauthenticated.'),
            ]);
    }

    public function test_lender_admin_user_not_verify_email_cant_index_webhooks_unsuccessfully()
    {
        $this->actingAs(self::$lenerAdminNotVerified)
            ->withHeader('X-Company', self::$company->id)
            ->getJson(self::$endpoint)
            ->assertStatus(403)
            ->assertJsonFragment([
                'message' => __('error.must_verify_email'),
                'code' => ErrorCode::EMAIL_NOT_VERIFIED,
            ]);
    }

    public function test_lender_admin_user_cant_access_index_webhooks_when_company_not_active_unsuccessfully()
    {
        $this->actingAs(self::$userLenderAdminBelongToCompanyNotActivated)
            ->withHeader('X-Company', self::$companyNotActivated->id)
            ->getJson(self::$endpoint)
            ->assertStatus(403)->assertJsonFragment([
                'message' => __('error.company_not_active'),
                'code' => ErrorCode::COMPANY_NOT_ACTIVE,
            ]);
    }

    public function test_lender_billing_cant_access_index_webhooks_unsuccessfully()
    {
        $this->actingAs(self::$lenderBilling)
            ->withHeader('X-Company', self::$company->id)
            ->getJson(self::$endpoint)
            ->assertStatus(403);
    }

    public function test_lender_order_creator_cant_access_index_webhooks_unsuccessfully()
    {
        $this->actingAs(self::$lenderCreator)
            ->withHeader('X-Company', self::$company->id)
            ->getJson(self::$endpoint)
            ->assertStatus(403);
    }

    public function test_lender_supervisor_cant_access_index_webhooks_unsuccessfully()
    {
        $this->actingAs(self::$lenderSuperVisor)
            ->withHeader('X-Company', self::$company->id)
            ->getJson(self::$endpoint)
            ->assertStatus(403);
    }

    public function test_lender_api_user_can_index_webhooks_successfully()
    {
        $this->actingAs(self::$lenderApiUser)
            ->withHeader('X-Company', self::$company->id)
            ->getJson(self::$endpoint)
            ->assertStatus(200);
    }
}
