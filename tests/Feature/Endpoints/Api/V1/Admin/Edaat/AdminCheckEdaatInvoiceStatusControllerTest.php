<?php

namespace Tests\Feature\Endpoints\Api\V1\Admin\Edaat;

use App\Enums\Action;
use App\Enums\Area;
use App\Enums\EdaatInvoiceStatus;
use App\Enums\Role;
use App\Enums\Subject;
use App\Enums\WalletType;
use App\Models\Company;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\InteractsWithAdmin;
use Tests\Traits\InteractsWithLender;

class AdminCheckEdaatInvoiceStatusControllerTest extends TestCase
{
    use RefreshDatabase;
    use InteractsWithLender;
    use InteractsWithAdmin;

    private static Company $company;

    private static Company $secondCompany;

    private static Wallet $wallet;

    private static Builder|Model $edaatInvoice;

    private static Builder|Model $paidEdaatInvoice;

    private static User $userLender;

    private static User $userLenderForSecondCompany;

    private static User $admin;

    private static User $manager;

    private static User $managerHasPermission;

    /**
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        [self::$company, self::$wallet] = $this->createCompany(
            '2000',
            [
                'company_cr' => '12345678910',
            ]
        );
        [self::$secondCompany] = $this->createCompany(
            '2000',
            [
                'company_cr' => '12345678999',
            ]
        );
        self::$managerHasPermission = $this->createManager(
            'managerHasPermission@bim.com',
            perm(Area::SuperAdmin, [Subject::LenderEdaatInvoices, Action::SyncStatusWithEdaat])
        );

        self::$userLender = $this->createLenderUser(self::$company->id, Role::LenderAdmin, 'lenderAdmin@bim.com');
        self::$userLenderForSecondCompany = $this->createLenderUser(self::$secondCompany->id, Role::LenderAdmin, 'lenderAdmin2@bim.com');
        self::$admin = $this->createAdmin();
        self::$manager = $this->createManager();
        self::$edaatInvoice = $this->createEdaatInvoice(self::$company->id, self::$userLender->id);
        self::$paidEdaatInvoice = $this->createEdaatInvoice(self::$company->id, self::$userLender->id, ['statud' => EdaatInvoiceStatus::Expired]);
    }

    public function test_admin_check_edaat_invoice_status_controller_successed()
    {
        $this->actingAs(self::$admin)
            ->postJson('api/v1/admin/edaat-invoices/'.self::$edaatInvoice->id.'/check-status')
            ->assertStatus(200);
    }

    public function test_admin_get_edaat_invoices_controller_can_not_deposit_twice()
    {
        $this->actingAs(self::$admin)
            ->postJson('api/v1/admin/edaat-invoices/'.self::$edaatInvoice->id.'/check-status')
            ->assertStatus(200);

        $balanceAfterFirstDeposit = self::$edaatInvoice->company->balance(WalletType::CompanyWallet);

        $this->actingAs(self::$admin)
            ->postJson('api/v1/admin/edaat-invoices/'.self::$edaatInvoice->id.'/check-status')
            ->assertStatus(200);

        $balanceAfterSecondDeposit = self::$edaatInvoice->company->balance(WalletType::CompanyWallet);

        $this->assertTrue($balanceAfterFirstDeposit->equals($balanceAfterSecondDeposit));
    }

    public function test_admin_get_edaat_invoices_controller_deposit_working_when_sync_status()
    {
        $balanceBeforeDeposit = self::$paidEdaatInvoice->company->balance(WalletType::CompanyWallet);

        $this->actingAs(self::$admin)
            ->postJson('api/v1/admin/edaat-invoices/'.self::$paidEdaatInvoice->id.'/check-status')
            ->assertStatus(200);

        $balanceAfterDeposit = self::$edaatInvoice->company->balance(WalletType::CompanyWallet);

        $this->assertTrue($balanceBeforeDeposit->equals($balanceAfterDeposit));
    }

    public function test_admin_get_edaat_invoices_controller_other_roles_can_not_access()
    {
        $this->assertLenderUserCannotAccess(function ($user, $role) {
            return $this->actingAs($user)
                ->postJson('api/v1/admin/edaat-invoices/'.self::$edaatInvoice->id.'/check-status');
        });
    }

    public function test_admin_get_edaat_invoices_controller_manager_can_not_access_with_no_permission()
    {
        $this->actingAs(self::$manager)
            ->postJson('api/v1/admin/edaat-invoices/'.self::$edaatInvoice->id.'/check-status')
            ->assertStatus(403);
    }

    public function test_admin_get_edaat_invoices_controller_manager_can_access_when_has_permission()
    {
        $this->actingAs(self::$managerHasPermission)
            ->postJson('api/v1/admin/edaat-invoices/'.self::$edaatInvoice->id.'/check-status')
            ->assertStatus(200);
    }
}
