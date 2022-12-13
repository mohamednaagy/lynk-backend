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
use Tests\Traits\InteractsWithLender;

class UserControllerIndexTest extends TestCase
{
    use RefreshDatabase, InteractsWithLender;

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

    /**
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        [self::$company, self::$wallet] = $this->createCompany('2000', ['company_cr' => '12345678910']);
        [self::$otherCompany, self::$otherWallet] = $this->createCompany('2000', ['company_cr' => '12345678911']);
        self::$userLenderAdmin = $this->createLenderUser(self::$company->id, Role::LenderAdmin, 'firstLenderAdmin@bim.com');
        self::$userLenderSupervisor = $this->createLenderUser(self::$company->id, Role::LenderSupervisor, 'lenderSupervisor@bim.com');
        self::$userLenderApi = $this->createLenderUser(self::$company->id, Role::LenderApiUser, 'lenderApi@bim.com');
        self::$userLenderBilling = $this->createLenderUser(self::$company->id, Role::LenderBilling, 'lenderBilling@bim.com');
        self::$userLenderOrderCreator = $this->createLenderUser(self::$company->id, Role::LenderOrderCreator, 'lenderOrderCreator@bim.com');
        self::$otherUserLenderAdmin = $this->createLenderUser(self::$otherCompany->id, Role::LenderAdmin, 'otherLenderAdmin@bim.com');
        self::$lenderUsersCollection = self::$company->users()->whereHas('roles', function ($query) {
            return $query->whereIn('name', [
                Role::LenderAdmin,
                Role::LenderOrderCreator,
                Role::LenderBilling,
                Role::LenderSupervisor,
            ]);
        })->paginate();
    }

    /**
     * @return void
     */
    public function test_that_un_auth_user_cant_index_lender_users(): void
    {
        $this->withHeader('X-Company', self::$company->id)
            ->getJson('api/v1/lender/users')
            ->assertUnauthorized()
            ->assertExactJson([
                'message' => __('Unauthenticated.'),
            ]);
    }

    /**
     * @return void
     */
    public function test_that_admin_user_can_index_lender_users(): void
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
                    ])->respond()
                    ->getData(true)
            );
    }

    /**
     * @return void
     */
    public function test_that_supervisor_user_cant_index_lender_users(): void
    {
        $this->actingAs(self::$userLenderSupervisor)
            ->withHeader('X-Company', self::$company->id)
            ->getJson('api/v1/lender/users')
            ->assertForbidden();
    }

    /**
     * @return void
     */
    public function test_that_billing_user_cant_index_lender_users(): void
    {
        $this->actingAs(self::$userLenderBilling)
            ->withHeader('X-Company', self::$company->id)
            ->getJson('api/v1/lender/users')
            ->assertForbidden();
    }

    /**
     * @return void
     */
    public function test_that_api_user_cant_index_lender_users(): void
    {
        $this->actingAs(self::$userLenderOrderCreator)
            ->withHeader('X-Company', self::$company->id)
            ->getJson('api/v1/lender/users')
            ->assertForbidden();
    }

    /**
     * @return void
     */
    public function test_that_order_creator_user_cant_index_lender_users(): void
    {
        $this->actingAs(self::$userLenderOrderCreator)
            ->withHeader('X-Company', self::$company->id)
            ->getJson('api/v1/lender/users')
            ->assertForbidden();
    }

    /**
     * @return void
     */
    public function test_that_lender_admin_user_cant_index_lender_users_case_company_pending(): void
    {
        self::$company->update([
            'status' => CompanyStatus::Pending,
        ]);

        $this->actingAs(self::$userLenderAdmin)
            ->withHeader('X-Company', self::$company->id)
            ->getJson('api/v1/lender/users')
            ->assertForbidden();
    }

    /**
     * @return void
     */
    public function test_that_lender_admin_user_cant_index_lender_users_case_company_under_review(): void
    {
        self::$company->update([
            'status' => CompanyStatus::UnderReview,
        ]);

        $this->actingAs(self::$userLenderAdmin)
            ->withHeader('X-Company', self::$company->id)
            ->getJson('api/v1/lender/users')
            ->assertForbidden();
    }

    /**
     * @return void
     */
    public function test_that_lender_admin_user_cant_index_lender_users_case_company_rejected(): void
    {
        self::$company->update([
            'status' => CompanyStatus::Rejected,
        ]);

        $this->actingAs(self::$userLenderAdmin)
            ->withHeader('X-Company', self::$company->id)
            ->getJson('api/v1/lender/users')
            ->assertForbidden();
    }

    /**
     * @return void
     */
    public function test_that_lender_admin_user_cant_index_lender_users_case_email_not_verified(): void
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
