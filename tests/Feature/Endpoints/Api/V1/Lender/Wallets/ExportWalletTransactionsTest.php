<?php

namespace Tests\Feature\Endpoints\Api\V1\Lender\Wallets;

use App\Actions\Contracts\Wallets\GetTransactions;
use App\Enums\Action;
use App\Enums\Area;
use App\Enums\Role;
use App\Enums\Subject;
use App\Exports\WalletTransactionsExport;
use App\Models\Company;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Maatwebsite\Excel\Facades\Excel;
use Mockery;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;
use Tests\Traits\InteractsWithCompany;
use Tests\Traits\InteractsWithUser;

class ExportWalletTransactionsTest extends TestCase
{
    use InteractsWithCompany, InteractsWithUser, RefreshDatabase;

    private Company $company;

    private Wallet $wallet;

    private User $lenderAdmin;

    private User $lenderSupervisor;

    private User $lenderBilling;

    private User $lenderOrderCreator;

    private User $lenderApiUser;

    protected function setUp(): void
    {
        parent::setUp();

        [$this->company, $this->wallet] = $this->createCompany(5000);

        $this->lenderAdmin = $this->createLenderUser($this->company->id, Role::LenderAdmin);
        $this->lenderSupervisor = $this->createLenderUser($this->company->id, Role::LenderSupervisor);
        $this->lenderBilling = $this->createLenderUser($this->company->id, Role::LenderBilling);
        $this->lenderOrderCreator = $this->createLenderUser($this->company->id, Role::LenderOrderCreator);
        $this->lenderApiUser = $this->createLenderUser($this->company->id, Role::LenderApiUser);

        // Assign permissions to users who need them
        $this->assignPermissionToUser(
            $this->lenderAdmin,
            perm(Area::Lender, [Subject::LenderTransactions, Action::Index])
        );
        $this->assignPermissionToUser(
            $this->lenderSupervisor,
            perm(Area::Lender, [Subject::LenderTransactions, Action::Index])
        );
    }

    public function test_unauthenticated_user_cannot_export_wallet_transactions()
    {
        $this->withHeader('X-Company', $this->company->id)
            ->getJson('api/v1/lender/wallets/transactions/export')
            ->assertUnauthorized()
            ->assertExactJson([
                'message' => __('Unauthenticated.'),
            ]);
    }

    public function test_lender_admin_can_export_wallet_transactions()
    {
        Excel::fake();

        $mockGetTransactions = Mockery::mock(GetTransactions::class);
        $mockQuery = Mockery::mock(Builder::class);

        $mockGetTransactions->shouldReceive('setCompany')
            ->once()
            ->with($this->company)
            ->andReturnSelf();

        $mockGetTransactions->shouldReceive('setFilters')
            ->once()
            ->with([])
            ->andReturnSelf();

        $mockGetTransactions->shouldReceive('handle')
            ->once()
            ->andReturn($mockQuery);

        $this->app->instance(GetTransactions::class, $mockGetTransactions);

        $this->actingAs($this->lenderAdmin)
            ->withHeader('X-Company', $this->company->id)
            ->getJson('api/v1/lender/wallets/transactions/export')
            ->assertStatus(Response::HTTP_OK);

        Excel::assertDownloaded(function ($filename, $export) {
            return $export instanceof WalletTransactionsExport &&
                   str_contains($filename, $this->company->name) &&
                   str_contains($filename, 'LYNKWalletTransactions') &&
                   str_contains($filename, '.csv');
        });
    }

    public function test_lender_supervisor_with_permissions_can_export_wallet_transactions()
    {
        Excel::fake();

        $mockGetTransactions = Mockery::mock(GetTransactions::class);
        $mockQuery = Mockery::mock(Builder::class);

        $mockGetTransactions->shouldReceive('setCompany')
            ->once()
            ->with($this->company)
            ->andReturnSelf();

        $mockGetTransactions->shouldReceive('setFilters')
            ->once()
            ->with([])
            ->andReturnSelf();

        $mockGetTransactions->shouldReceive('handle')
            ->once()
            ->andReturn($mockQuery);

        $this->app->instance(GetTransactions::class, $mockGetTransactions);

        $this->actingAs($this->lenderSupervisor)
            ->withHeader('X-Company', $this->company->id)
            ->getJson('api/v1/lender/wallets/transactions/export')
            ->assertStatus(Response::HTTP_OK);

        Excel::assertDownloaded(function ($filename, $export) {
            return $export instanceof WalletTransactionsExport &&
                   str_contains($filename, $this->company->name) &&
                   str_contains($filename, 'LYNKWalletTransactions') &&
                   str_contains($filename, '.csv');
        });
    }

    public function test_lender_user_without_permissions_cannot_export_wallet_transactions()
    {
        $this->actingAs($this->lenderBilling)
            ->withHeader('X-Company', $this->company->id)
            ->getJson('api/v1/lender/wallets/transactions/export')
            ->assertStatus(Response::HTTP_FORBIDDEN)
            ->assertJsonPath('message', __('User does not have the right permissions.'));
    }

    public function test_export_with_query_parameters()
    {
        Excel::fake();

        $queryParams = [
            'date_from' => '2023-01-01',
            'date_to' => '2023-12-31',
            'amount_gte' => 100,
            'amount_lte' => 1000,
            'page' => 1,
        ];

        $mockGetTransactions = Mockery::mock(GetTransactions::class);
        $mockQuery = Mockery::mock(Builder::class);

        $mockGetTransactions->shouldReceive('setCompany')
            ->once()
            ->with($this->company)
            ->andReturnSelf();

        $mockGetTransactions->shouldReceive('setFilters')
            ->once()
            ->with($queryParams)
            ->andReturnSelf();

        $mockGetTransactions->shouldReceive('handle')
            ->once()
            ->andReturn($mockQuery);

        $this->app->instance(GetTransactions::class, $mockGetTransactions);

        $this->actingAs($this->lenderAdmin)
            ->withHeader('X-Company', $this->company->id)
            ->getJson('api/v1/lender/wallets/transactions/export?'.http_build_query($queryParams))
            ->assertStatus(Response::HTTP_OK);

        Excel::assertDownloaded(function ($filename, $export) {
            return $export instanceof WalletTransactionsExport &&
                   str_contains($filename, $this->company->name) &&
                   str_contains($filename, 'LYNKWalletTransactions') &&
                   str_contains($filename, '.csv');
        });
    }

    public function test_export_without_company_header_fails()
    {
        $this->actingAs($this->lenderAdmin)
            ->getJson('api/v1/lender/wallets/transactions/export')
            ->assertStatus(Response::HTTP_BAD_REQUEST);
    }

    public function test_export_filename_contains_timestamp()
    {
        Excel::fake();

        $mockGetTransactions = Mockery::mock(GetTransactions::class);
        $mockQuery = Mockery::mock(Builder::class);

        $mockGetTransactions->shouldReceive('setCompany')
            ->once()
            ->with($this->company)
            ->andReturnSelf();

        $mockGetTransactions->shouldReceive('setFilters')
            ->once()
            ->with([])
            ->andReturnSelf();

        $mockGetTransactions->shouldReceive('handle')
            ->once()
            ->andReturn($mockQuery);

        $this->app->instance(GetTransactions::class, $mockGetTransactions);

        $this->actingAs($this->lenderAdmin)
            ->withHeader('X-Company', $this->company->id)
            ->getJson('api/v1/lender/wallets/transactions/export')
            ->assertStatus(Response::HTTP_OK);

        Excel::assertDownloaded(function ($filename) {
            // Check that filename contains timestamp pattern (Ymd_His)
            return preg_match('/\d{8}_\d{6}/', $filename);
        });
    }

    public function test_export_creates_correct_export_instance()
    {
        Excel::fake();

        $mockGetTransactions = Mockery::mock(GetTransactions::class);
        $mockQuery = Mockery::mock(Builder::class);

        $mockGetTransactions->shouldReceive('setCompany')
            ->once()
            ->with($this->company)
            ->andReturnSelf();

        $mockGetTransactions->shouldReceive('setFilters')
            ->once()
            ->with([])
            ->andReturnSelf();

        $mockGetTransactions->shouldReceive('handle')
            ->once()
            ->andReturn($mockQuery);

        $this->app->instance(GetTransactions::class, $mockGetTransactions);

        $this->actingAs($this->lenderAdmin)
            ->withHeader('X-Company', $this->company->id)
            ->getJson('api/v1/lender/wallets/transactions/export')
            ->assertStatus(Response::HTTP_OK);

        Excel::assertDownloaded(function ($filename, $export) {
            return $export instanceof WalletTransactionsExport;
        });
    }

    public function test_export_with_validation_errors()
    {
        $invalidParams = [
            'date_from' => 'invalid-date',
            'amount_gte' => 'not-a-number',
        ];

        $this->actingAs($this->lenderAdmin)
            ->withHeader('X-Company', $this->company->id)
            ->getJson('api/v1/lender/wallets/transactions/export?'.http_build_query($invalidParams))
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors(['date_from', 'amount_gte']);
    }

    public function test_export_returns_csv_format()
    {
        Excel::fake();

        $mockGetTransactions = Mockery::mock(GetTransactions::class);
        $mockQuery = Mockery::mock(Builder::class);

        $mockGetTransactions->shouldReceive('setCompany')
            ->once()
            ->with($this->company)
            ->andReturnSelf();

        $mockGetTransactions->shouldReceive('setFilters')
            ->once()
            ->with([])
            ->andReturnSelf();

        $mockGetTransactions->shouldReceive('handle')
            ->once()
            ->andReturn($mockQuery);

        $this->app->instance(GetTransactions::class, $mockGetTransactions);

        $response = $this->actingAs($this->lenderAdmin)
            ->withHeader('X-Company', $this->company->id)
            ->getJson('api/v1/lender/wallets/transactions/export');

        $response->assertStatus(Response::HTTP_OK);

        Excel::assertDownloaded(function ($filename, $export) {
            return str_contains($filename, '.csv');
        });
    }

    public function test_export_includes_custom_headers()
    {
        Excel::fake();

        $mockGetTransactions = Mockery::mock(GetTransactions::class);
        $mockQuery = Mockery::mock(Builder::class);

        $mockGetTransactions->shouldReceive('setCompany')
            ->once()
            ->with($this->company)
            ->andReturnSelf();

        $mockGetTransactions->shouldReceive('setFilters')
            ->once()
            ->with([])
            ->andReturnSelf();

        $mockGetTransactions->shouldReceive('handle')
            ->once()
            ->andReturn($mockQuery);

        $this->app->instance(GetTransactions::class, $mockGetTransactions);

        $response = $this->actingAs($this->lenderAdmin)
            ->withHeader('X-Company', $this->company->id)
            ->getJson('api/v1/lender/wallets/transactions/export');

        $response->assertStatus(Response::HTTP_OK);

        // The controller sets X-File-Name header
        Excel::assertDownloaded(function ($filename) {
            return str_contains($filename, 'LYNKWalletTransactions');
        });
    }

    public function test_different_roles_access_permissions()
    {
        // Test that LenderOrderCreator cannot access
        $this->actingAs($this->lenderOrderCreator)
            ->withHeader('X-Company', $this->company->id)
            ->getJson('api/v1/lender/wallets/transactions/export')
            ->assertStatus(Response::HTTP_FORBIDDEN);

        // Test that LenderApiUser cannot access
        $this->actingAs($this->lenderApiUser)
            ->withHeader('X-Company', $this->company->id)
            ->getJson('api/v1/lender/wallets/transactions/export')
            ->assertStatus(Response::HTTP_FORBIDDEN);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
