<?php

namespace Tests\Feature\Endpoints\Api\V1\Admin\Lenders\Clients;

use App\Enums\Action;
use App\Enums\Area;
use App\Enums\CompanyLenderClientType;
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
    protected function setUp(): void
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

    public function test_authenticated_admin_can_filter_lender_clients_by_name(): void
    {
        $client1 = $this->createClient(self::$company, ['name' => 'Test Client 1']);
        $client2 = $this->createClient(self::$company, ['name' => 'Test Client 2']);

        $this->actingAs(self::$userAdmin)
            ->getJson(self::$endpoint.'?name=Test Client 1')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(['name' => 'Test Client 1'])
            ->assertJsonMissing(['name' => 'Test Client 2']);
    }

    public function test_authenticated_admin_can_filter_lender_clients_by_type(): void
    {
        $client1 = $this->createClient(self::$company, ['type' => CompanyLenderClientType::Individual]);
        $client2 = $this->createClient(self::$company, ['type' => CompanyLenderClientType::Business]);

        $this->actingAs(self::$userAdmin)
            ->getJson(self::$endpoint.'?type='.CompanyLenderClientType::Individual)
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(['type' => [
                'value' => CompanyLenderClientType::Individual,
                'description' => 'Individual',
            ]])
            ->assertJsonMissing(['type' => [
                'value' => CompanyLenderClientType::Business,
                'description' => 'Business',
            ]]);
    }

    public function test_authenticated_admin_can_filter_lender_clients_by_national_id(): void
    {
        $client1 = $this->createClient(self::$company, ['national_id' => '1234567890']);
        $client2 = $this->createClient(self::$company, ['national_id' => '0987654321']);

        $this->actingAs(self::$userAdmin)
            ->getJson(self::$endpoint.'?national_id=1234567890')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(['national_id' => '1234567890'])
            ->assertJsonMissing(['national_id' => '0987654321']);
    }

    public function test_authenticated_admin_can_filter_lender_clients_with_multiple_parameters(): void
    {
        $client1 = $this->createClient(self::$company, [
            'name' => 'Test Client 1',
            'type' => CompanyLenderClientType::Individual,
            'national_id' => '1234567890',
        ]);
        $client2 = $this->createClient(self::$company, [
            'name' => 'Test Client 2',
            'type' => CompanyLenderClientType::Business,
            'national_id' => '0987654321',
        ]);

        $this->actingAs(self::$userAdmin)
            ->getJson(self::$endpoint.'?name=Test Client 1&type='.CompanyLenderClientType::Individual.'&national_id=1234567890')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment([
                'name' => 'Test Client 1',
                'type' => [
                    'value' => CompanyLenderClientType::Individual,
                    'description' => 'Individual',
                ],
                'national_id' => '1234567890',
            ])
            ->assertJsonMissing([
                'name' => 'Test Client 2',
                'type' => [
                    'value' => CompanyLenderClientType::Business,
                    'description' => 'Business',
                ],
                'national_id' => '0987654321',
            ]);
    }

    public function test_authenticated_admin_fails_to_filter_lender_clients_with_invalid_type(): void
    {
        $this->actingAs(self::$userAdmin)
            ->getJson(self::$endpoint.'?type=invalid_type')
            ->assertUnprocessable()
            ->assertJson([
                'message' => __('validation.exists', ['attribute' => 'type']),
                'errors' => [
                    'type' => [
                        __('validation.exists', ['attribute' => 'type']),
                    ],
                ],
            ]);
    }

    public function test_authenticated_admin_fails_to_filter_lender_clients_with_invalid_national_id(): void
    {
        $this->actingAs(self::$userAdmin)
            ->getJson(self::$endpoint.'?national_id=invalid_id')
            ->assertUnprocessable()
            ->assertJson([
                'message' => __('validation.integer', ['attribute' => 'national ID']).' (and 1 more error)',
                'errors' => [
                    'national_id' => [
                        __('validation.integer', ['attribute' => 'national ID']),
                        __('validation.max_digits'),
                    ],
                ],
            ]);
    }

    public function test_authenticated_admin_fails_to_filter_lender_clients_with_too_long_name(): void
    {
        $this->actingAs(self::$userAdmin)
            ->getJson(self::$endpoint.'?name='.str_repeat('a', 101))
            ->assertUnprocessable()
            ->assertJson([
                'message' => 'The name must not be greater than 100 characters.',
                'errors' => [
                    'name' => [
                        'The name must not be greater than 100 characters.',
                    ],
                ],
            ]);
    }
}
