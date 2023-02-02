<?php

namespace Tests\Feature\Endpoints\Api\V1\Lender\Users;

use App\Enums\CompanyStatus;
use App\Enums\Role;
use App\Models\Company;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\InteractsWithCompany;
use Tests\Traits\InteractsWithUser;

class UserControllerDestroyTest extends TestCase
{
    use RefreshDatabase, InteractsWithUser, InteractsWithCompany;

    private static Company $company;

    private static Wallet $wallet;

    private static Company $otherCompany;

    private static Wallet $otherWallet;

    private static User $userLenderAdmin;

    private static User $userLenderSupervisor;

    private static User $userLenderBilling;

    private static User $userLenderApi;

    private static User $userLenderOrderCreator;

    private static User $otherUserLenderAdmin;

    /**
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        [self::$company, self::$wallet] = $this->createCompany('2000', ['company_cr' => '12345678910']);
        [self::$otherCompany, self::$otherWallet] = $this->createCompany('2000', ['company_cr' => '12345678911']);
        self::$userLenderAdmin = $this->createLenderUser(self::$company->id, Role::LenderAdmin);
        self::$userLenderSupervisor = $this->createLenderUser(self::$company->id, Role::LenderSupervisor);
        self::$userLenderApi = $this->createLenderUser(self::$company->id, Role::LenderApiUser);
        self::$userLenderBilling = $this->createLenderUser(self::$company->id, Role::LenderBilling);
        self::$userLenderOrderCreator = $this->createLenderUser(self::$company->id, Role::LenderOrderCreator);
        self::$otherUserLenderAdmin = $this->createLenderUser(self::$otherCompany->id, Role::LenderAdmin);
    }

    /**
     * @return void
     */
    public function test_un_auth_user_cant_delete_lender_user_unsuccessful(): void
    {
        $this->withHeader('X-Company', self::$company->id)
            ->deleteJson('api/v1/lender/users/'.self::$userLenderAdmin->id)
            ->assertUnauthorized()
            ->assertExactJson([
                'message' => __('Unauthenticated.'),
            ]);
    }

    /**
     * @return void
     */
    public function test_lender_admin_user_can_delete_lender_user_successful(): void
    {
        $lenderUserCount = self::$company->users()->count();

        $this->actingAs(self::$userLenderAdmin)
            ->withHeader('X-Company', self::$company->id)
            ->deleteJson(
                'api/v1/lender/users/'.
                    self::$company->users()
                        ->latest('created_at')->first()->id
            )
            ->assertOk()
            ->assertExactJson([
                'data' => [],
            ]);

        $newLenderUserCount = self::$company->users()->count();

        $this->assertEquals($newLenderUserCount, $lenderUserCount - 1);
    }

    /**
     * @return void
     */
    public function test_lender_supervisor_user_cant_delete_lender_user_unsuccessful(): void
    {
        $this->actingAs(self::$userLenderSupervisor)
            ->withHeader('X-Company', self::$company->id)
            ->deleteJson('api/v1/lender/users/'.self::$company->users()
                ->latest('created_at')->first()->id)
            ->assertForbidden();
    }

    /**
     * @return void
     */
    public function test_lender_billing_user_cant_delete_lender_user_unsuccessful(): void
    {
        $this->actingAs(self::$userLenderBilling)
            ->withHeader('X-Company', self::$company->id)
            ->deleteJson('api/v1/lender/users/'.self::$userLenderAdmin->id)
            ->assertForbidden();
    }

    /**
     * @return void
     */
    public function test_lender_api_user_cant_delete_lender_user_unsuccessful(): void
    {
        $this->actingAs(self::$userLenderApi)
            ->withHeader('X-Company', self::$company->id)
            ->deleteJson('api/v1/lender/users/'.self::$userLenderAdmin->id)
            ->assertForbidden();
    }

    /**
     * @return void
     */
    public function test_lender_order_creator_user_cant_delete_lender_user_unsuccessful(): void
    {
        $this->actingAs(self::$userLenderOrderCreator)
            ->withHeader('X-Company', self::$company->id)
            ->deleteJson('api/v1/lender/users/'.self::$userLenderAdmin->id)
            ->assertForbidden();
    }

    /**
     * @return void
     */
    public function test_lender_admin_user_cant_delete_lender_user_in_other_company_unsuccessful(): void
    {
        $this->actingAs(self::$userLenderAdmin)
            ->withHeader('X-Company', self::$company->id)
            ->deleteJson('api/v1/lender/users/'.self::$otherUserLenderAdmin->id)
            ->assertNotFound();
    }

    /**
     * @return void
     */
    public function test_lender_lender_admin_user_cant_delete_lender_users_case_company_pending_unsuccessful(): void
    {
        self::$company->update([
            'status' => CompanyStatus::Pending,
        ]);

        $this->actingAs(self::$userLenderAdmin)
            ->withHeader('X-Company', self::$company->id)
            ->deleteJson('api/v1/lender/users/'.self::$userLenderSupervisor->id)
            ->assertForbidden();
    }

    /**
     * @return void
     */
    public function test_lender_lender_admin_user_cant_delete_lender_users_case_company_under_review_unsuccessful(): void
    {
        self::$company->update([
            'status' => CompanyStatus::UnderReview,
        ]);

        $this->actingAs(self::$userLenderAdmin)
            ->withHeader('X-Company', self::$company->id)
            ->deleteJson('api/v1/lender/users/'.self::$userLenderSupervisor->id)
            ->assertForbidden();
    }

    /**
     * @return void
     */
    public function test_lender_lender_admin_user_cant_delete_lender_users_case_company_rejected_unsuccessful(): void
    {
        self::$company->update([
            'status' => CompanyStatus::Rejected,
        ]);

        $this->actingAs(self::$userLenderAdmin)
            ->withHeader('X-Company', self::$company->id)
            ->deleteJson('api/v1/lender/users/'.self::$userLenderSupervisor->id)
            ->assertForbidden();
    }

    /**
     * @return void
     */
    public function test_lender_lender_admin_user_cant_delete_lender_users_case_email_not_verified_unsuccessful(): void
    {
        self::$userLenderAdmin->update([
            'email_verified_at' => null,
        ]);

        $this->actingAs(self::$userLenderAdmin)
            ->withHeader('X-Company', self::$company->id)
            ->deleteJson('api/v1/lender/users/'.self::$userLenderSupervisor->id)
            ->assertForbidden();
    }
}
