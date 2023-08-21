<?php

namespace Tests\Feature\Endpoints\Api\V1\Lender\Users;

use App\Enums\Area;
use App\Enums\CompanyStatus;
use App\Enums\Role;
use App\Models\Company;
use App\Models\User;
use App\Models\Wallet;
use App\Transformers\UserTransformer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Tests\TestCase;
use Tests\Traits\InteractsWithCompany;
use Tests\Traits\InteractsWithUser;

class UserControllerIndexTest extends TestCase
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

    private static LengthAwarePaginator $lenderUsersCollection;

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
        self::$lenderUsersCollection = self::$company->users()->whereHas('roles', function ($query) {
            return $query->whereIn('name', [
                Role::LenderAdmin,
                Role::LenderOrderCreator,
                Role::LenderBilling,
                Role::LenderSupervisor,
            ]);
        })->paginate();
    }

    public function test_un_auth_user_cant_index_lender_users_unsuccessful(): void
    {
        $this->withHeader('X-Company', self::$company->id)
            ->getJson('api/v1/lender/users')
            ->assertUnauthorized()
            ->assertExactJson([
                'message' => __('Unauthenticated.'),
            ]);
    }

    public function test_lender_admin_user_can_index_lender_users_successful(): void
    {
        $this->actingAs(self::$userLenderAdmin)
            ->withHeader('X-Company', self::$company->id)
            ->getJson('api/v1/lender/users')
            ->assertOk()
            ->assertExactJson(
                fractal(self::$lenderUsersCollection, new UserTransformer(Area::Lender))
                    ->parseIncludes([
                        'id',
                        'first_name',
                        'last_name',
                        'email',
                        'phone_number',
                        'phone_country_code',
                        'formatted_phone_number',
                        'role',
                        'is_invitation_accepted',
                    ])->respond()
                    ->getData(true)
            );
    }

    public function test_lender_supervisor_user_cant_index_lender_user_unsuccessful(): void
    {
        $this->actingAs(self::$userLenderSupervisor)
            ->withHeader('X-Company', self::$company->id)
            ->getJson('api/v1/lender/users')
            ->assertForbidden();
    }

    public function test_lender_billing_user_cant_index_lender_users_unsuccessful(): void
    {
        $this->actingAs(self::$userLenderBilling)
            ->withHeader('X-Company', self::$company->id)
            ->getJson('api/v1/lender/users')
            ->assertForbidden();
    }

    public function test_lender_api_user_cant_index_lender_users_unsuccessful(): void
    {
        $this->actingAs(self::$userLenderApi)
            ->withHeader('X-Company', self::$company->id)
            ->getJson('api/v1/lender/users')
            ->assertForbidden();
    }

    public function test_lender_order_creator_user_cant_index_lender_users_unsuccessful(): void
    {
        $this->actingAs(self::$userLenderOrderCreator)
            ->withHeader('X-Company', self::$company->id)
            ->getJson('api/v1/lender/users')
            ->assertForbidden();
    }

    public function test_lender_lender_admin_user_cant_index_lender_users_case_company_pending_unsuccessful(): void
    {
        self::$company->update([
            'status' => CompanyStatus::Pending,
        ]);

        $this->actingAs(self::$userLenderAdmin)
            ->withHeader('X-Company', self::$company->id)
            ->getJson('api/v1/lender/users')
            ->assertForbidden();
    }

    public function test_lender_lender_admin_user_cant_index_lender_users_case_company_under_review_unsuccessful(): void
    {
        self::$company->update([
            'status' => CompanyStatus::UnderReview,
        ]);

        $this->actingAs(self::$userLenderAdmin)
            ->withHeader('X-Company', self::$company->id)
            ->getJson('api/v1/lender/users')
            ->assertForbidden();
    }

    public function test_lender_lender_admin_user_cant_index_lender_users_case_company_rejected_unsuccessful(): void
    {
        self::$company->update([
            'status' => CompanyStatus::Rejected,
        ]);

        $this->actingAs(self::$userLenderAdmin)
            ->withHeader('X-Company', self::$company->id)
            ->getJson('api/v1/lender/users')
            ->assertForbidden();
    }

    public function test_lender_lender_admin_user_cant_index_lender_users_case_email_not_verified_unsuccessful(): void
    {
        self::$userLenderAdmin->update([
            'email_verified_at' => null,
        ]);

        $this->actingAs(self::$userLenderAdmin)
            ->withHeader('X-Company', self::$company->id)
            ->getJson('api/v1/lender/users')
            ->assertForbidden();
    }
}
