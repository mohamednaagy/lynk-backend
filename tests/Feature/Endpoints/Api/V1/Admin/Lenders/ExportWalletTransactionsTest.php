<?php

namespace Tests\Feature\Endpoints\Api\V1\Admin\Lenders;

use App\Actions\Contracts\Wallets\GetTransactions;
use App\Enums\Action;
use App\Enums\Area;
use App\Enums\Role;
use App\Enums\Subject;
use App\Exports\WalletTransactionsExport;
use App\Models\Company;
use App\Models\User;
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

    private User $superAdminUser;

    private User $managerAdminUser;

    private Company $lender;

    protected function setUp(): void
    {
        parent::setUp();

        $this->superAdminUser = $this->createSuperAdminUser();
        $this->managerAdminUser = $this->createSuperAdminUser(Role::Manager);
        $this->assignPermissionToUser(
            $this->managerAdminUser,
            perm(Area::SuperAdmin, [Subject::LenderTransactions, Action::Index, Action::Show])
        );

        [$this->lender] = $this->createCompany(5000);
    }

    public function test_unauthenticated_user_cannot_export_wallet_transactions()
    {
        $this->getJson("api/v1/admin/lenders/{$this->lender->id}/wallet-transactions/export")
            ->assertUnauthorized()
            ->assertExactJson([
                'message' => __('Unauthenticated.'),
            ]);
    }

    public function test_super_admin_can_export_wallet_transactions()
    {
        Excel::fake();

        $mockGetTransactions = Mockery::mock(GetTransactions::class);
        $mockQuery = Mockery::mock(Builder::class);

        $mockGetTransactions->shouldReceive('handle')
            ->once()
            ->with($this->lender, [])
            ->andReturn($mockQuery);

        $this->app->instance(GetTransactions::class, $mockGetTransactions);

        $this->actingAs($this->superAdminUser)
            ->getJson("api/v1/admin/lenders/{$this->lender->id}/wallet-transactions/export")
            ->assertStatus(Response::HTTP_OK);

        Excel::assertDownloaded(function ($filename, $export) {
            return $export instanceof WalletTransactionsExport &&
                   str_contains($filename, 'wallet-transactions-'.$this->lender->name) &&
                   str_contains($filename, '.xlsx');
        });
    }

    public function test_manager_admin_with_permissions_can_export_wallet_transactions()
    {
        Excel::fake();

        $mockGetTransactions = Mockery::mock(GetTransactions::class);
        $mockQuery = Mockery::mock(Builder::class);

        $mockGetTransactions->shouldReceive('handle')
            ->once()
            ->with($this->lender, [])
            ->andReturn($mockQuery);

        $this->app->instance(GetTransactions::class, $mockGetTransactions);

        $this->actingAs($this->managerAdminUser)
            ->getJson("api/v1/admin/lenders/{$this->lender->id}/wallet-transactions/export")
            ->assertStatus(Response::HTTP_OK);

        Excel::assertDownloaded(function ($filename, $export) {
            return $export instanceof WalletTransactionsExport &&
                   str_contains($filename, 'wallet-transactions-'.$this->lender->name) &&
                   str_contains($filename, '.xlsx');
        });
    }

    public function test_manager_admin_without_permissions_cannot_export_wallet_transactions()
    {
        // Remove permissions from manager
        $this->assignPermissionToUser($this->managerAdminUser, []);

        $this->actingAs($this->managerAdminUser)
            ->getJson("api/v1/admin/lenders/{$this->lender->id}/wallet-transactions/export")
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

        $mockGetTransactions->shouldReceive('handle')
            ->once()
            ->with($this->lender, $queryParams)
            ->andReturn($mockQuery);

        $this->app->instance(GetTransactions::class, $mockGetTransactions);

        $this->actingAs($this->superAdminUser)
            ->getJson("api/v1/admin/lenders/{$this->lender->id}/wallet-transactions/export?".http_build_query($queryParams))
            ->assertStatus(Response::HTTP_OK);

        Excel::assertDownloaded(function ($filename, $export) {
            return $export instanceof WalletTransactionsExport &&
                   str_contains($filename, 'wallet-transactions-'.$this->lender->name) &&
                   str_contains($filename, '.xlsx');
        });
    }

    public function test_export_with_invalid_lender_returns_404()
    {
        $this->actingAs($this->superAdminUser)
            ->getJson('api/v1/admin/lenders/999999/wallet-transactions/export')
            ->assertNotFound();
    }

    public function test_export_filename_contains_timestamp()
    {
        Excel::fake();

        $mockGetTransactions = Mockery::mock(GetTransactions::class);
        $mockQuery = Mockery::mock(Builder::class);

        $mockGetTransactions->shouldReceive('handle')
            ->once()
            ->with($this->lender, [])
            ->andReturn($mockQuery);

        $this->app->instance(GetTransactions::class, $mockGetTransactions);

        $this->actingAs($this->superAdminUser)
            ->getJson("api/v1/admin/lenders/{$this->lender->id}/wallet-transactions/export")
            ->assertStatus(Response::HTTP_OK);

        Excel::assertDownloaded(function ($filename) {
            // Check that filename contains timestamp pattern (Y-m-d-H-i-s)
            return preg_match('/\d{4}-\d{2}-\d{2}-\d{2}-\d{2}-\d{2}/', $filename);
        });
    }

    public function test_export_creates_correct_export_instance()
    {
        Excel::fake();

        $mockGetTransactions = Mockery::mock(GetTransactions::class);
        $mockQuery = Mockery::mock(Builder::class);

        $mockGetTransactions->shouldReceive('handle')
            ->once()
            ->with($this->lender, [])
            ->andReturn($mockQuery);

        $this->app->instance(GetTransactions::class, $mockGetTransactions);

        $this->actingAs($this->superAdminUser)
            ->getJson("api/v1/admin/lenders/{$this->lender->id}/wallet-transactions/export")
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

        $this->actingAs($this->superAdminUser)
            ->getJson("api/v1/admin/lenders/{$this->lender->id}/wallet-transactions/export?".http_build_query($invalidParams))
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors(['date_from', 'amount_gte']);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
