<?php

namespace Tests\Feature\Endpoints\Api\V1\Admin\Lenders;

use App\Enums\Action;
use App\Enums\Area;
use App\Enums\CompanyStatus;
use App\Enums\Role;
use App\Enums\Subject;
use App\Models\Company;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Arr;
use Modules\Grantify\Facades\Grantify;
use Tests\TestCase;
use Tests\Traits\InteractsWithAdmin;
use Tests\Traits\InteractsWithLender;

class UpdateLenderStatusTest extends TestCase
{
    use RefreshDatabase, InteractsWithLender, InteractsWithAdmin;

    private static Company $lender;

    private static Wallet $wallet;

    private static User $userAdmin;

    private static User $userManager;

    private static User $userLenderAdmin;

    private static array $lenderStatusDetails;

    /**
     * @return void
     *
     * @throws BindingResolutionException
     */
    public function setUp(): void
    {
        parent::setUp();

        [self::$lender, self::$wallet] = $this->createCompany('2000', ['company_cr' => '12345678910']);
        self::$userAdmin = $this->createAdmin('admin@bim.com');
        self::$userManager = $this->createManager(
            'manager@bim.com',
            perm(Area::SuperAdmin, [Subject::Lenders, Action::Edit]),
        );
        self::$userLenderAdmin = $this->createLenderUser(self::$lender->id, Role::LenderAdmin, 'lenderAdmin@bim.com');
        self::$lenderStatusDetails = [
            'status' => CompanyStatus::Approved(),
            'public_status_comment' => 'Approved public',
            'internal_status_comment' => 'Approved internal',
        ];
    }

    /**
     * @return void
     */
    public function test_that_un_auth_user_cant_update_lender_status(): void
    {
        $this->putJson('api/v1/admin/lenders/'.self::$lender->id.'/status', self::$lenderStatusDetails)
            ->assertUnauthorized()
            ->assertExactJson([
                'message' => __('Unauthenticated.'),
            ]);
    }

    /**
     * @return void
     */
    public function test_that_admin_can_update_lender_status(): void
    {
        $this->actingAs(self::$userAdmin)
            ->putJson('api/v1/admin/lenders/'.self::$lender->id.'/status', self::$lenderStatusDetails)
            ->assertOk()
            ->assertExactJson([
                'data' => [],
            ]);
    }

    /**
     * @return void
     */
    public function test_that_manager_can_update_lender_status(): void
    {
        $this->actingAs(self::$userManager)
            ->putJson('api/v1/admin/lenders/'.self::$lender->id.'/status', self::$lenderStatusDetails)
            ->assertOk()
            ->assertExactJson([
                'data' => [],
            ]);
    }

    /**
     * @return void
     */
    public function test_that_manager_without_permissions_cant_update_lender_status(): void
    {
        Grantify::syncPermissionToModel(self::$userManager, []);

        $this->actingAs(self::$userManager)
            ->putJson('api/v1/admin/lenders/'.self::$lender->id.'/status', self::$lenderStatusDetails)
            ->assertForbidden();
    }

    /**
     * @return void
     */
    public function test_that_admin_can_update_lender_status_and_see_updates_in_get_auth(): void
    {
        $this->actingAs(self::$userAdmin)
            ->putJson('api/v1/admin/lenders/'.self::$lender->id.'/status', self::$lenderStatusDetails)
            ->assertOk()
            ->assertExactJson([
                'data' => [],
            ]);

        $this->actingAs(self::$userLenderAdmin)
            ->withHeader('X-Company', self::$lender->id)
            ->getJson('api/v1/lender/auth')
            ->assertOk()
            ->assertSee([
                'public_status_comment' => 'Approved public',
            ]);
    }

    /**
     * @return void
     */
    public function test_that_admin_cant_update_lender_status_without_status(): void
    {
        $this->actingAs(self::$userAdmin)
            ->putJson('api/v1/admin/lenders/'.self::$lender->id.'/status', Arr::except(self::$lenderStatusDetails, 'status'))
            ->assertUnprocessable()
            ->assertExactJson([
                'message' => 'The status field is required.',
                'errors' => [
                    'status' => [
                        'The status field is required.',
                    ],
                ],
            ]);
    }

    /**
     * @return void
     */
    public function test_that_admin_cant_update_lender_status_without_public_status_comment(): void
    {
        $this->actingAs(self::$userAdmin)
            ->putJson('api/v1/admin/lenders/'.self::$lender->id.'/status', Arr::except(self::$lenderStatusDetails, 'public_status_comment'))
            ->assertUnprocessable()
            ->assertExactJson([
                'message' => 'The public status comment field is required.',
                'errors' => [
                    'public_status_comment' => [
                        'The public status comment field is required.',
                    ],
                ],
            ]);
    }

    /**
     * @return void
     */
    public function test_that_admin_cant_update_lender_status_without_internal_status_comment(): void
    {
        $this->actingAs(self::$userAdmin)
            ->putJson('api/v1/admin/lenders/'.self::$lender->id.'/status', Arr::except(self::$lenderStatusDetails, 'internal_status_comment'))
            ->assertUnprocessable()
            ->assertExactJson([
                'message' => 'The internal status comment field is required.',
                'errors' => [
                    'internal_status_comment' => [
                        'The internal status comment field is required.',
                    ],
                ],
            ]);
    }
}
