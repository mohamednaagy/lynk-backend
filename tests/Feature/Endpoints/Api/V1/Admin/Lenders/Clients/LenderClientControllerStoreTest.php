<?php

namespace Tests\Feature\Endpoints\Api\V1\Admin\Lenders\Clients;

use App\Enums\Action;
use App\Enums\Area;
use App\Enums\CompanyLenderClientType;
use App\Enums\Role;
use App\Enums\Subject;
use App\Models\ClientAutoSellPeriod;
use App\Models\Company;
use App\Models\CompanyLenderClient;
use App\Models\User;
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

    private static Company $mainCompany;

    private static User $superAdminUser;


    private static User $userManager;

    private static LengthAwarePaginator $users;

    private static string $createClientEndpoint;

    private static array $sampleCreateDataWithoutPeriods;

    private static array $sampleCreateDataWithPeriods;

    private const INVALID_COMPANY_ID = 900;

    public function setUp(): void
    {
        parent::setUp();
        [self::$mainCompany] = $this->createLenderCompany();
        $this->initializeUsers();
        $this->initializeEndpointsAndPayloads();
    }

    private function buildEndpoint(int $companyId, int $clientId): string
    {
        return route('api.v1.admins.clients.store', [
            'lender' => $companyId,
        ]);
    }

    private function initializeCompanies(): void
    {
        [self::$mainCompany] = $this->createLenderCompany();
    }

    private function initializeUsers(): void
    {
        self::$superAdminUser = $this->createSuperAdminUser();
        self::$userManager = $this->createSuperAdminUser(Role::Manager);
        $this->assignPermissionToUser(
            self::$userManager,
            perm(Area::SuperAdmin, [Subject::LenderClients, Action::Create])
        );
    }

    private function initializeEndpointsAndPayloads(): void
    {
        self::$createClientEndpoint = route('api.v1.admins.clients.store', [
            'lender' => self::$mainCompany->id,
        ]);

        self::$sampleCreateDataWithoutPeriods = [
            'name' => 'Created Client Name',
            'national_id' => '1472583695',
            'type' => CompanyLenderClientType::Individual,
            'auto_complete_sell' => 'false',
        ];

        self::$sampleCreateDataWithPeriods = [
            'name' => 'Created Client Name 2',
            'national_id' => '1472583695',
            'auto_complete_sell' => 'true',
            'type' => CompanyLenderClientType::Individual,
            'auto_sell_periods' => [
                [
                    'effective_start' => '2025-01-01',
                    'effective_end' => '2025-12-31',
                ],
                [
                    'effective_start' => '2026-01-01',
                    'effective_end' => '2026-12-31',
                ],
            ],
        ];
    }

    public function test_unauthenticated_admin_fails_to_create_lender_client(): void
    {
        $this->postJson(self::$createClientEndpoint)
            ->assertUnauthorized()
            ->assertExactJson([
                'message' => __('Unauthenticated.'),
            ]);
    }

    public function test_authenticated_admin_fails_to_create_lender_client_when_lender_not_found(): void
    {
        $this->actingAs(self::$superAdminUser)
            ->postJson('api/v1/admin/lenders/'.self::INVALID_COMPANY_ID.'/clients')
            ->assertNotFound();
    }

    public function test_authenticated_admin_success_create_lender_client(): void
    {
        $this->actingAs(self::$superAdminUser)
            ->postJson(self::$createClientEndpoint, self::$sampleCreateDataWithoutPeriods)
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'name',
                    'type',
                    'national_id',
                    'auto_complete_sell',

                ],
            ]);

        self::$sampleCreateDataWithoutPeriods['company_id'] = self::$mainCompany->id;
        $this->assertDatabaseHas(CompanyLenderClient::class, self::$sampleCreateDataWithoutPeriods);
    }

    public function test_authenticated_admin_success_create_lender_client_and_not_create_period_when_auto_complete_sell_is_false(): void
    {
        self::$sampleCreateDataWithPeriods['auto_complete_sell'] = 'false';
        $this->actingAs(self::$superAdminUser)
            ->postJson(self::$createClientEndpoint, self::$sampleCreateDataWithPeriods)
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'name',
                    'type',
                    'national_id',
                    'auto_complete_sell',

                ],
            ]);

        self::$sampleCreateDataWithoutPeriods['company_id'] = self::$mainCompany->id;
        $this->assertEquals(0, ClientAutoSellPeriod::count());

    }

    public function test_authenticated_admin_success_create_lender_client_and_periods_when_auto_complete_sell_is_true(): void
    {
        $this->actingAs(self::$superAdminUser)
            ->postJson(self::$createClientEndpoint, self::$sampleCreateDataWithPeriods)
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'name',
                    'type',
                    'national_id',
                    'auto_complete_sell',

                ],
            ]);

        self::$sampleCreateDataWithoutPeriods['company_id'] = self::$mainCompany->id;
        $this->assertEquals(count(self::$sampleCreateDataWithPeriods['auto_sell_periods']), ClientAutoSellPeriod::count());

    }

    public function test_authenticated_manager_success_create_lender_client(): void
    {
        $this->actingAs(self::$userManager)
            ->postJson(self::$createClientEndpoint, self::$sampleCreateDataWithoutPeriods)
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'name',
                    'type',
                    'national_id',
                    'auto_complete_sell',
                ],
            ]);

        self::$sampleCreateDataWithoutPeriods['company_id'] = self::$mainCompany->id;
        $this->assertDatabaseHas(CompanyLenderClient::class, self::$sampleCreateDataWithoutPeriods);
    }

    public function test_authenticated_manager_without_permissions_fails_to_create_lender_client(): void
    {
        Grantify::syncPermissionToModel(self::$userManager, []);

        $this->actingAs(self::$userManager)
            ->postJson(self::$createClientEndpoint)
            ->assertForbidden();
    }

    public function test_authenticated_admin_fails_to_create_lender_client_when_national_id_exist(): void
    {
        $client = $this->createClient(self::$mainCompany);
        self::$sampleCreateDataWithoutPeriods['national_id'] = $client->national_id;
        $this->actingAs(self::$superAdminUser)
            ->postJson(self::$createClientEndpoint, self::$sampleCreateDataWithoutPeriods)
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
        self::$sampleCreateDataWithoutPeriods['type'] = 100;
        $this->actingAs(self::$superAdminUser)
            ->postJson(self::$createClientEndpoint, self::$sampleCreateDataWithoutPeriods)
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

        self::$sampleCreateDataWithoutPeriods['national_id'] = '14785236951';
        $this->actingAs(self::$superAdminUser)
            ->postJson(self::$createClientEndpoint, self::$sampleCreateDataWithoutPeriods)
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

        self::$sampleCreateDataWithoutPeriods['national_id'] = 'nationalid';
        $this->actingAs(self::$superAdminUser)
            ->postJson(self::$createClientEndpoint, self::$sampleCreateDataWithoutPeriods)
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

        self::$sampleCreateDataWithoutPeriods['name'] = Str::random(101);
        $this->actingAs(self::$superAdminUser)
            ->postJson(self::$createClientEndpoint, self::$sampleCreateDataWithoutPeriods)
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

    public function test_authenticated_admin_fails_to_create_lender_client_when_send_not_boolean_value_at_auto_compelete_sell(): void
    {
        self::$sampleCreateDataWithoutPeriods['auto_complete_sell'] = 'invalid_value';
        $this->actingAs(self::$superAdminUser)
            ->postJson(self::$createClientEndpoint, self::$sampleCreateDataWithoutPeriods)
            ->assertUnprocessable()
            ->assertJson([
                'message' => __('validation.field_should_be_boolean', ['attribute' => 'auto complete sell']),
                'errors' => [
                    'auto_complete_sell' => [
                        __('validation.field_should_be_boolean', ['attribute' => 'auto complete sell']),
                    ],
                ],
            ]);
    }

    public function test_authenticated_admin_fails_to_create_lender_client_when_not_send_auto_sell_periods_if_auto_complete_sell_is_true(): void
    {
        self::$sampleCreateDataWithoutPeriods['auto_complete_sell'] = 'true';
        $this->actingAs(self::$superAdminUser)
            ->postJson(self::$createClientEndpoint, self::$sampleCreateDataWithoutPeriods)
            ->assertUnprocessable()
            ->assertJson([
                'message' => __('validation.field_is_required'),
                'errors' => [
                    'auto_sell_periods' => [
                        __('validation.field_is_required'),
                    ],
                ],
            ]);
    }

    public function test_authenticated_admin_fails_to_create_lender_client_when_effective_end_date_less_than_effective_start_date(): void
    {
        self::$sampleCreateDataWithPeriods['auto_sell_periods'] = [
            [
                'effective_start' => '2025-01-01',
                'effective_end' => '2024-12-31',
            ],
        ];

        $this->actingAs(self::$superAdminUser)
            ->postJson(self::$createClientEndpoint, self::$sampleCreateDataWithPeriods)
            ->assertUnprocessable()
            ->assertJson([
                'message' => __('validation.effective_end_after_or_equal_start'),
                'errors' => [
                    'auto_sell_periods.0.effective_end' => [
                        __('validation.effective_end_after_or_equal_start'),
                    ],

                ],
            ]);
    }

    public function test_authenticated_admin_fails_to_create_lender_client_when_auto_compelete_sell_is_true_but_not_send_effective_start_and_effective_end(): void
    {
        self::$sampleCreateDataWithPeriods['auto_sell_periods'] = [
            [
                'effective_start' => '',
                'effective_end' => '',
            ],
        ];

        $this->actingAs(self::$superAdminUser)
            ->postJson(self::$createClientEndpoint, self::$sampleCreateDataWithPeriods)
            ->assertUnprocessable()
            ->assertJson([
                'message' => __('validation.required_if', ['attribute' => 'auto_sell_periods.0.effective_start', 'other' => 'auto complete sell', 'value' => 'true']).' (and 1 more error)',
                'errors' => [
                    'auto_sell_periods.0.effective_start' => [
                        __('validation.required_if', ['attribute' => 'auto_sell_periods.0.effective_start', 'other' => 'auto complete sell', 'value' => 'true']),
                    ],
                    'auto_sell_periods.0.effective_end' => [
                        __('validation.required_if', ['attribute' => 'auto_sell_periods.0.effective_end', 'other' => 'auto complete sell', 'value' => 'true']),

                    ],

                ],
            ]);
    }

    public function test_authenticated_admin_fails_to_create_lender_client_when_send_incorrect_formatted_of_dates(): void
    {
        self::$sampleCreateDataWithPeriods['auto_sell_periods'] = [
            [
                'effective_start' => '01-04-2025',
                'effective_end' => '01-04-2025',
            ],
        ];

        $this->actingAs(self::$superAdminUser)
            ->postJson(self::$createClientEndpoint, self::$sampleCreateDataWithPeriods)
            ->assertUnprocessable()
            ->assertJson([
                'message' => __('validation.invalid_date_format').' (and 1 more error)',
                'errors' => [
                    'auto_sell_periods.0.effective_start' => [
                        __('validation.invalid_date_format'),
                    ],
                    'auto_sell_periods.0.effective_end' => [
                        __('validation.invalid_date_format'),

                    ],

                ],
            ]);
    }

    public function test_authenticated_admin_fails_to_create_lender_client_when_send_periods_with_overlapped(): void
    {
        self::$sampleCreateDataWithPeriods['auto_sell_periods'] = [
            [
                'effective_start' => '2025-01-01',
                'effective_end' => '2026-01-01',
            ],
            [
                'effective_start' => '2025-06-01',
                'effective_end' => '2025-09-01',
            ],
        ];

        $this->actingAs(self::$superAdminUser)
            ->postJson(self::$createClientEndpoint, self::$sampleCreateDataWithPeriods)
            ->assertUnprocessable()
            ->assertJson([
                'message' => __('validation.periods_overlapped'),
                'errors' => [
                    'auto_sell_periods' => [
                        __('validation.periods_overlapped'),
                    ],

                ],
            ]);
    }

    public function test_authenticated_admin_fails_to_create_lender_client_when_send_empty_feilds(): void
    {
        $this->actingAs(self::$superAdminUser)
            ->postJson(self::$createClientEndpoint, [])
            ->assertUnprocessable()
            ->assertJson([
                'message' => __('validation.field_is_required').' (and 3 more errors)',
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
                    'auto_complete_sell' => [
                        __('validation.field_is_required'),
                    ],
                ],
            ]);
    }
}
