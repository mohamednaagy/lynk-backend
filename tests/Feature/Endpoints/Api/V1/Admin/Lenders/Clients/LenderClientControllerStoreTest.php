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
use Illuminate\Support\Str;
use Modules\Grantify\Facades\Grantify;
use Tests\TestCase;
use Tests\Traits\InteractsWithCompany;
use Tests\Traits\InteractsWithLenderClient;
use Tests\Traits\InteractsWithUser;

class LenderClientControllerStoreTest extends TestCase
{
    use InteractsWithCompany, InteractsWithLenderClient , InteractsWithUser, RefreshDatabase;

    private static Company $company;

    //    private static Wallet $wallet;

    private static User $userAdmin;

    private static CompanyLenderClient $client;

    private static User $userManager;

    private static LengthAwarePaginator $users;

    private static string $endpoint;

    private static array $lenderClientDetails;

    /**
     * @throws BindingResolutionException
     */
    public function setUp(): void
    {
        parent::setUp();
        [self::$company] = $this->createLenderCompany();
        self::$userAdmin = $this->createSuperAdminUser();
        self::$userManager = $this->createSuperAdminUser(Role::Manager);
        $this->assignPermissionToUser(self::$userManager, perm(Area::SuperAdmin, [Subject::LenderClients, Action::Create]));
        self::$endpoint = 'api/v1/admin/lenders/'.self::$company->id.'/clients';
        self::$lenderClientDetails = [
            'name' => 'new client',
            'type' => CompanyLenderClientType::Individual,
            'national_id' => '1472583695',
        ];
    }

    public function test_unauthenticated_admin_fails_to_create_lender_client(): void
    {
        $this->postJson(self::$endpoint)
            ->assertUnauthorized()
            ->assertExactJson([
                'message' => __('Unauthenticated.'),
            ]);
    }

    public function test_authenticated_admin_fails_to_create_lender_client_when_lender_not_found(): void
    {
        $this->actingAs(self::$userAdmin)
            ->postJson('api/v1/admin/lenders/900/clients')
            ->assertNotFound();
    }

    public function test_authenticated_admin_success_create_lender_client(): void
    {
        $this->actingAs(self::$userAdmin)
            ->postJson(self::$endpoint, self::$lenderClientDetails)
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'name',
                    'type',
                    'national_id',
                ],
            ]);

        self::$lenderClientDetails['company_id'] = self::$company->id;
        $this->assertDatabaseHas(CompanyLenderClient::class, self::$lenderClientDetails);
    }

    public function test_authenticated_manager_success_create_lender_client(): void
    {
        $this->actingAs(self::$userManager)
            ->postJson(self::$endpoint, self::$lenderClientDetails)
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'name',
                    'type',
                    'national_id',
                ],
            ]);

        self::$lenderClientDetails['company_id'] = self::$company->id;
        $this->assertDatabaseHas(CompanyLenderClient::class, self::$lenderClientDetails);
    }

    public function test_authenticated_manager_without_permissions_fails_to_create_lender_client(): void
    {
        Grantify::syncPermissionToModel(self::$userManager, []);

        $this->actingAs(self::$userManager)
            ->postJson(self::$endpoint)
            ->assertForbidden();
    }

    public function test_authenticated_admin_fails_to_create_lender_client_when_national_id_exist(): void
    {
        $client = $this->createClient(self::$company);
        self::$lenderClientDetails['national_id'] = $client->national_id;
        $this->actingAs(self::$userAdmin)
            ->postJson(self::$endpoint, self::$lenderClientDetails)
            ->assertUnprocessable()
            ->assertJson([
                'message' => __('validation.unique_input'),
                'errors' => [
                    'national_id' => [
                        __('validation.unique_input'),
                    ],
                ],
            ]);
    }

    public function test_authenticated_admin_fails_to_create_lender_client_when_type_is_invalid(): void
    {
        self::$lenderClientDetails['type'] = 100;
        $this->actingAs(self::$userAdmin)
            ->postJson(self::$endpoint, self::$lenderClientDetails)
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

    public function test_authenticated_admin_fails_to_create_lender_client_when_max_digit_of_national_id(): void
    {

        self::$lenderClientDetails['national_id'] = '14785236951';
        $this->actingAs(self::$userAdmin)
            ->postJson(self::$endpoint, self::$lenderClientDetails)
            ->assertUnprocessable()
            ->assertJson([
                'message' => __('validation.max_digits'),
                'errors' => [
                    'national_id' => [
                        __('validation.max_digits'),
                    ],
                ],
            ]);
    }

    public function test_authenticated_admin_fails_to_create_lender_client_with_non_integer_national_id(): void
    {

        self::$lenderClientDetails['national_id'] = 'nationalid';
        $this->actingAs(self::$userAdmin)
            ->postJson(self::$endpoint, self::$lenderClientDetails)
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

    public function test_authenticated_admin_fails_to_create_lender_client_when_max_string_chars_of_name(): void
    {

        self::$lenderClientDetails['name'] = Str::random(101);
        $this->actingAs(self::$userAdmin)
            ->postJson(self::$endpoint, self::$lenderClientDetails)
            ->assertUnprocessable()
            ->assertJson([
                'message' => __('validation.max_string_chars', ['max' => 100]),
                'errors' => [
                    'name' => [
                        __('validation.max_string_chars', ['max' => 100]),
                    ],
                ],
            ]);
    }

    public function test_authenticated_admin_fails_to_create_lender_client_when_send_empty_feilds(): void
    {
        $this->actingAs(self::$userAdmin)
            ->postJson(self::$endpoint, [])
            ->assertUnprocessable()
            ->assertJson([
                'message' => __('validation.field_is_required').' (and 2 more errors)',
                'errors' => [
                    'name' => [
                        __('validation.field_is_required'),
                    ],
                    'national_id' => [
                        __('validation.field_is_required'),
                    ],
                    'type' => [
                        __('validation.field_is_required'),
                    ],
                ],
            ]);
    }
}
