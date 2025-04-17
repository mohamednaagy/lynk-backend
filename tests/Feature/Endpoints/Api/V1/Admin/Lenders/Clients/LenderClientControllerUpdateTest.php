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
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;
use Tests\TestCase;
use Tests\Traits\InteractsWithCompany;
use Tests\Traits\InteractsWithLenderClient;
use Tests\Traits\InteractsWithUser;

class LenderClientControllerUpdateTest extends TestCase
{
    use InteractsWithCompany, InteractsWithLenderClient , InteractsWithUser, RefreshDatabase;

    private static Company $mainCompany;

    private static Company $otherCompany;

    private static User $superAdminUser;

    private static CompanyLenderClient $mainCompanyClient;

    private static CompanyLenderClient $otherCompanyClient;

    private static CompanyLenderClient $client;

    private static ClientAutoSellPeriod $autoSellPeriodForMainClient;

    private static User $userManager;

    private static LengthAwarePaginator $users;

    private static string $updateClientEndpoint;

    private static array $sampleUpdateDataWithoutPeriods;

    private static array $sampleUpdateDataWithPeriods;

    private const INVALID_COMPANY_ID = 900;

    private const INVALID_CLIENT_ID = 900;

    /**
     * @throws BindingResolutionException
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->initializeCompanies();
        $this->initializeUsers();
        $this->initializeLenderClients();
        $this->initializeEndpointsAndPayloads();
    }

    private function buildEndpoint(int $companyId, int $clientId): string
    {
        return route('api.v1.admins.clients.update', [
            'lender' => $companyId,
            'client' => $clientId,
        ]);
    }

    private function initializeCompanies(): void
    {
        [self::$mainCompany] = $this->createLenderCompany();
        [self::$otherCompany] = $this->createLenderCompany();
    }

    private function initializeUsers(): void
    {
        self::$superAdminUser = $this->createSuperAdminUser();
        self::$userManager = $this->createSuperAdminUser(Role::Manager);
        $this->assignPermissionToUser(
            self::$userManager,
            perm(Area::SuperAdmin, [Subject::LenderClients, Action::Edit])
        );
    }

    private function initializeLenderClients(): void
    {
        self::$mainCompanyClient = $this->createClient(self::$mainCompany);
        self::$autoSellPeriodForMainClient = $this->createPeriodsForClient(self::$mainCompanyClient);
        self::$otherCompanyClient = $this->createClient(self::$otherCompany);
    }

    private function initializeEndpointsAndPayloads(): void
    {
        self::$updateClientEndpoint = route('api.v1.admins.clients.update', [
            'lender' => self::$mainCompany->id,
            'client' => self::$mainCompanyClient->id,
        ]);

        self::$sampleUpdateDataWithoutPeriods = [
            'name' => 'Updated Client Name',
            'national_id' => '1472583695',
            'auto_complete_sell' => false,
        ];

        self::$sampleUpdateDataWithPeriods = [
            'name' => 'Updated Client Name',
            'national_id' => '1472583695',
            'auto_complete_sell' => true,
            'auto_sell_periods' => [
                [
                    'id' => self::$autoSellPeriodForMainClient->id,
                    'effective_start' => '2025-01-01',
                    'effective_end' => '2025-12-31',
                ],
                [
                    'effective_start' => '2025-01-01',
                    'effective_end' => '2025-12-31',
                ],
            ],
        ];
    }

    public function test_unauthenticated_user_cannot_update_lender_client(): void
    {
        $this->putJson(self::$updateClientEndpoint)
            ->assertUnauthorized()
            ->assertExactJson([
                'message' => __('Unauthenticated.'),
            ]);
    }

    public function test_authenticated_admin_fails_when_lender_does_not_exist(): void
    {
        $this->actingAs(self::$superAdminUser)
            ->putJson($this->buildEndpoint(self::INVALID_COMPANY_ID, self::INVALID_CLIENT_ID))
            ->assertNotFound();
    }

    public function test_authenticated_admin_fails_to_update_when_lender_not_found(): void
    {
        $this->actingAs(self::$superAdminUser)
            ->putJson($this->buildEndpoint(self::$mainCompany->id, self::$otherCompanyClient->id))
            ->assertForbidden();
    }

    public function test_authenticated_admin_fails_to_update_when_client_does_not_belong_to_lender(): void
    {
        $this->actingAs(self::$superAdminUser)
            ->putJson($this->buildEndpoint(self::$mainCompany->id, self::$otherCompanyClient->id))
            ->assertForbidden();
    }

    public function test_authenticated_admin_fails_to_update_lender_client_when_national_id_exist(): void
    {
        $client2 = $this->createClient(self::$mainCompany);
        self::$sampleUpdateDataWithoutPeriods['national_id'] = $client2->national_id;
        $this->actingAs(self::$superAdminUser)
            ->putJson(self::$updateClientEndpoint, self::$sampleUpdateDataWithoutPeriods)
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

    public function test_authenticated_admin_fails_to_update_lender_client_when_attempt_to_edit_type_field(): void
    {
        self::$sampleUpdateDataWithoutPeriods['type'] = CompanyLenderClientType::Individual;
        $this->actingAs(self::$superAdminUser)
            ->putJson(self::$updateClientEndpoint, self::$sampleUpdateDataWithoutPeriods)
            ->assertUnprocessable()
            ->assertJson([
                'message' => __('validation.field_is_not_editable', ['attribute' => 'type']),
                'errors' => [
                    'type' => [
                        __('validation.field_is_not_editable', ['attribute' => 'type']),
                    ],
                ],
            ]);
    }

    public function test_authenticated_admin_fails_to_update_lender_client_when_max_digit_of_national_id(): void
    {

        self::$sampleUpdateDataWithoutPeriods['national_id'] = '1111111111111';
        $this->actingAs(self::$superAdminUser)
            ->putJson(self::$updateClientEndpoint, self::$sampleUpdateDataWithoutPeriods)
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

    public function test_authenticated_admin_fails_to_update_lender_client_with_non_integer_national_id(): void
    {

        self::$sampleUpdateDataWithoutPeriods['national_id'] = 'nationalid';
        $this->actingAs(self::$superAdminUser)
            ->putJson(self::$updateClientEndpoint, self::$sampleUpdateDataWithoutPeriods)
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

    public function test_authenticated_admin_fails_to_update_lender_client_when_max_string_chars_of_name(): void
    {

        self::$sampleUpdateDataWithoutPeriods['name'] = Str::random(101);
        $this->actingAs(self::$superAdminUser)
            ->putJson(self::$updateClientEndpoint, self::$sampleUpdateDataWithoutPeriods)
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

    public function test_authenticated_admin_fails_to_update_lender_client_when_send_empty_fields(): void
    {
        $this->actingAs(self::$superAdminUser)
            ->putJson(self::$updateClientEndpoint, [])
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
                ],
            ]);
    }

    public function test_authenticated_admin_fails_to_update_lender_client_when_auto_complete_sell_is_true_but_not_send_periods(): void
    {
        self::$sampleUpdateDataWithPeriods['auto_sell_periods'] = [];
        $this->actingAs(self::$superAdminUser)
            ->putJson(self::$updateClientEndpoint, self::$sampleUpdateDataWithPeriods)
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

    public function test_authenticated_admin_fails_to_update_lender_client_when_effective_end_date_less_than_effective_start_date(): void
    {
        self::$sampleUpdateDataWithPeriods['auto_sell_periods'] = [
            [
                'id' => self::$autoSellPeriodForMainClient->id,
                'effective_start' => '2025-01-01',
                'effective_end' => '2024-12-31',
            ],
        ];

        $this->actingAs(self::$superAdminUser)
            ->putJson(self::$updateClientEndpoint, self::$sampleUpdateDataWithPeriods)
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

    public function test_authenticated_admin_fails_to_update_lender_client_when_auto_compelete_sell_is_true_but_not_send_effective_start_and_effective_end(): void
    {
        self::$sampleUpdateDataWithPeriods['auto_sell_periods'] = [
            [
                'effective_start' => '',
                'effective_end' => '',
            ],
        ];

        $this->actingAs(self::$superAdminUser)
            ->putJson(self::$updateClientEndpoint, self::$sampleUpdateDataWithPeriods)
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

    public function test_authenticated_admin_fails_to_update_lender_client_when_send_incorrect_formatted_of_dates(): void
    {
        self::$sampleUpdateDataWithPeriods['auto_sell_periods'] = [
            [
                'effective_start' => '01-04-2025',
                'effective_end' => '01-04-2025',
            ],
        ];

        $this->actingAs(self::$superAdminUser)
            ->putJson(self::$updateClientEndpoint, self::$sampleUpdateDataWithPeriods)
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

    public function test_authenticated_admin_fails_to_update_lender_client_when_send_periods_with_overlapped(): void
    {
        self::$sampleUpdateDataWithPeriods['auto_sell_periods'] = [
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
            ->putJson(self::$updateClientEndpoint, self::$sampleUpdateDataWithPeriods)
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

    public function test_authenticated_admin_fails_to_update_lender_client_when_send_id_of_period_not_belong_to_lender_client(): void
    {
        $otherPeriod = $this->createPeriodsForClient(self::$otherCompanyClient);

        self::$sampleUpdateDataWithPeriods['auto_sell_periods'] = [
            [
                'id' => $otherPeriod->id,
                'effective_start' => '2025-01-01',
                'effective_end' => '2026-01-01',
            ],
        ];

        $this->actingAs(self::$superAdminUser)
            ->putJson(self::$updateClientEndpoint, self::$sampleUpdateDataWithPeriods)
            ->assertUnprocessable()
            ->assertJson([
                'message' => __('validation.exists', ['attribute' => 'auto_sell_periods.0.id']),
                'errors' => [
                    'auto_sell_periods.0.id' => [
                        __('validation.exists', ['attribute' => 'auto_sell_periods.0.id']),
                    ],

                ],
            ]);
    }

    public function test_authenticated_admin_success_to_update_lender_client_when_auto_complete_sell_is_false(): void
    {
        $this->actingAs(self::$superAdminUser)
            ->putJson(self::$updateClientEndpoint, self::$sampleUpdateDataWithoutPeriods)
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'name',
                    'type',
                    'national_id',
                    'auto_complete_sell',
                    'auto_sell_periods',
                ],
            ]);
    }

    /**
     * @return void
     *              if exist id will update it
     *              if not exist id will add it
     */
    public function test_authenticated_admin_success_to_update_lender_client_with_periods(): void
    {
        self::$sampleUpdateDataWithPeriods['auto_sell_periods'] = [
            [
                'id' => self::$autoSellPeriodForMainClient->id,
                'effective_start' => '2024-01-01',
                'effective_end' => '2024-01-01',
            ],
            [
                'effective_start' => '2025-01-01',
                'effective_end' => '2025-01-01',
            ],
            [
                'effective_start' => '2026-06-01',
                'effective_end' => '2026-09-01',
            ],
        ];

        $this->actingAs(self::$superAdminUser)
            ->putJson(self::$updateClientEndpoint, self::$sampleUpdateDataWithPeriods)
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'name',
                    'type',
                    'national_id',
                    'auto_complete_sell',
                    'auto_sell_periods',
                ],
            ]);

        $this->assertEquals(3, ClientAutoSellPeriod::where('company_lender_client_id', self::$mainCompanyClient->id)->count());
    }
}
