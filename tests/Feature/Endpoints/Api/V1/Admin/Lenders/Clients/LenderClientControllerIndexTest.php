<?php

namespace Tests\Feature\Endpoints\Api\V1\Admin\Lenders\Clients;

use App\Enums\Action;
use App\Enums\Area;
use App\Enums\Role;
use App\Enums\Subject;
use App\Models\Company;
use App\Models\CompanyLenderClient;
use App\Models\User;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Grantify\Facades\Grantify;
use Tests\TestCase;
use Tests\Traits\InteractsWithCompany;
use Tests\Traits\InteractsWithLenderClient;
use Tests\Traits\InteractsWithUser;

class LenderClientControllerIndexTest extends TestCase
{
    use InteractsWithCompany, InteractsWithLenderClient , InteractsWithUser, RefreshDatabase;

    private static Company $company;

    //    private static Wallet $wallet;

    private static User $userAdmin;

    private static CompanyLenderClient $client;

    private static User $userManager;

    private static LengthAwarePaginator $users;

    private static string $endpoint;

    /**
     * @throws BindingResolutionException
     */
    public function setUp(): void
    {
        parent::setUp();
        [self::$company] = $this->createLenderCompany();
        self::$userAdmin = $this->createSuperAdminUser();
        self::$client = $this->createClient(self::$company);
        self::$userManager = $this->createSuperAdminUser(Role::Manager);
        $this->assignPermissionToUser(self::$userManager, perm(Area::SuperAdmin, [Subject::LenderClients, Action::Index]));
        self::$endpoint = 'api/v1/admin/lenders/'.self::$company->id.'/clients';
    }

    public function test_unauthenticated_admin_cannot_list_lender_clients(): void
    {
        $this->getJson(self::$endpoint)
            ->assertUnauthorized()
            ->assertExactJson([
                'message' => __('Unauthenticated.'),
            ]);
    }

    public function test_authenticated_admin_cannot_list_lender_clients_when_lender_not_found(): void
    {
        $this->actingAs(self::$userAdmin)
            ->getJson('api/v1/admin/lenders/900/clients')
            ->assertNotFound();
    }

    public function test_authenticated_admin_cannot_list_lender_clients_when_missing_lender_paramater(): void
    {
        $this->actingAs(self::$userAdmin)
            ->getJson('api/v1/admin/lenders/clients')
            ->assertNotFound();
    }

    public function test_authenticated_admin_can_list_lender_clients(): void
    {
        $this->actingAs(self::$userAdmin)
            ->getJson(self::$endpoint)
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    [
                        'id',
                        'name',
                        'type',
                        'national_id',
                    ],
                ],
            ]);
    }

    public function test_authenticated_manager_without_permissions_cant_list_lender_clients(): void
    {
        Grantify::syncPermissionToModel(self::$userManager, []);

        $this->actingAs(self::$userManager)
            ->getJson(self::$endpoint)
            ->assertForbidden();
    }

    public function test_authenticated_manager_can_list_lender_clients(): void
    {
        $this->actingAs(self::$userManager)
            ->getJson(self::$endpoint)
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    [
                        'id',
                        'name',
                        'type',
                        'national_id',
                    ],
                ],
            ]);
    }
}
