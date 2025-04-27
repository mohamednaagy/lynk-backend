<?php

namespace Tests\Feature\Endpoints\Api\V1\Admin\Lenders\Clients;

use App\Enums\Action;
use App\Enums\Area;
use App\Enums\Role;
use App\Enums\Subject;
use App\Models\ClientAutoSellPeriod;
use App\Models\Company;
use App\Models\CompanyLenderClient;
use App\Models\User;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Tests\TestCase;
use Tests\Traits\InteractsWithCompany;
use Tests\Traits\InteractsWithLenderClient;
use Tests\Traits\InteractsWithUser;

class LenderClientControllerDeleteTest extends TestCase
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
    }

    private function buildEndpoint(int $companyId, int $clientId): string
    {
        return route('api.v1.admins.clients.destroy', [
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

    public function test_unauthenticated_user_cannot_delete_lender_client(): void
    {
        $this
            ->deleteJson($this->buildEndpoint(self::$mainCompany->id, self::$mainCompanyClient->id))
            ->assertUnauthorized()
            ->assertExactJson([
                'message' => __('Unauthenticated.'),
            ]);
    }

    public function test_authenticated_admin_fails_when_lender_does_not_exist(): void
    {
        $this->actingAs(self::$superAdminUser)
            ->deleteJson($this->buildEndpoint(self::INVALID_COMPANY_ID, self::INVALID_CLIENT_ID))
            ->assertNotFound();
    }

    public function test_authenticated_admin_fails_when_client_does_not_exist(): void
    {
        $this->actingAs(self::$superAdminUser)
            ->deleteJson($this->buildEndpoint(self::$mainCompany->id, self::INVALID_CLIENT_ID))
            ->assertNotFound();
    }

    public function test_authenticated_admin_fails_to_delete_when_client_does_not_belong_to_lender(): void
    {
        $this->actingAs(self::$superAdminUser)
            ->deleteJson($this->buildEndpoint(self::$mainCompany->id, self::$otherCompanyClient->id))
            ->assertForbidden();
    }

    public function test_authenticated_admin_success_to_delete_lender_client_with_periods(): void
    {
        $this->actingAs(self::$superAdminUser)
            ->deleteJson($this->buildEndpoint(self::$mainCompany->id, self::$mainCompanyClient->id))
            ->assertOk()
            ->assertJsonStructure([
                'data' => [],
            ]);

        $this->assertEquals(0, ClientAutoSellPeriod::where('company_lender_client_id', self::$mainCompanyClient->id)->count());
    }
}
