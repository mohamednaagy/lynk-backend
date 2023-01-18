<?php

namespace Tests\Feature\Endpoints\Api\V1\Admin\Edaat;

use App\Enums\Action;
use App\Enums\Area;
use App\Enums\Role;
use App\Enums\Subject;
use App\Models\Company;
use App\Models\EdaatInvoice;
use App\Models\User;
use App\Models\Wallet;
use App\Transformers\EdaatInvoiceTransformer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;
use Tests\Traits\AssertsAccessByRoleAndArea;

class AdminGetEdaatInvoicesControllerTest extends TestCase
{
    use RefreshDatabase;
    use AssertsAccessByRoleAndArea;

    private static Company $company;

    private static Company $secondCompany;

    private static Wallet $wallet;

    private static Builder|Model $edaatInvoice;

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
        self::$managerHasPermission = $this->createSuperAdminUser(Role::Manager);
        $this->assignPermissionToUser(self::$managerHasPermission, perm(Area::SuperAdmin, [Subject::LenderEdaatInvoices, Action::Index]));

        self::$userLender = $this->createLenderUser(self::$company->id, Role::LenderAdmin);
        self::$userLenderForSecondCompany = $this->createLenderUser(self::$secondCompany->id, Role::LenderAdmin);
        self::$admin = $this->createSuperAdminUser();
        self::$manager = $this->createSuperAdminUser(Role::Manager);
        $this->createEdaatInvoice(self::$company->id, self::$userLender->id);
        $this->createEdaatInvoice(self::$secondCompany->id, self::$userLenderForSecondCompany->id);
    }

    public function test_admin_get_edaat_invoices_controller_successed()
    {
        $edaatInvoices = EdaatInvoice::with(['company', 'creator'])
            ->paginate();

        $this->actingAs(self::$admin)
            ->getJson('api/v1/admin/edaat-invoices')
            ->assertStatus(Response::HTTP_OK)
            ->assertExactJson(
                fractal($edaatInvoices, new EdaatInvoiceTransformer())
                    ->parseIncludes([
                        'id',
                        'invoice_number',
                        'amount',
                        'amount_formatted',
                        'creator',
                        'company_name',
                        'company_number',
                        'status',
                        'company',
                    ])
                    ->respond()
                    ->getData(true)
            );
    }

    public function test_admin_get_edaat_invoices_controller_filltered_by_company_id()
    {
        $edaatInvoices = EdaatInvoice::with(['company', 'creator'])
            ->where('company_id', self::$company->id)
            ->paginate();

        $this->actingAs(self::$admin)
            ->getJson('api/v1/admin/edaat-invoices?company_id='.self::$company->id)
            ->assertStatus(Response::HTTP_OK)
            ->assertExactJson(
                fractal($edaatInvoices, new EdaatInvoiceTransformer())
                    ->parseIncludes([
                        'id',
                        'invoice_number',
                        'amount',
                        'amount_formatted',
                        'creator',
                        'company_name',
                        'company_number',
                        'status',
                        'company',
                    ])
                    ->respond()
                    ->getData(true)
            );
    }

    public function test_admin_get_edaat_invoices_controller_filltered_by_invoice_number()
    {
        $invoiceNumber = EdaatInvoice::first()->invoice_number;

        $edaatInvoices = EdaatInvoice::with(['company', 'creator'])
            ->where('invoice_number', $invoiceNumber)
            ->paginate();

        $this->actingAs(self::$admin)
            ->getJson('api/v1/admin/edaat-invoices?invoice_number='.$invoiceNumber)
            ->assertStatus(Response::HTTP_OK)
            ->assertExactJson(
                fractal($edaatInvoices, new EdaatInvoiceTransformer())
                    ->parseIncludes([
                        'id',
                        'invoice_number',
                        'amount',
                        'amount_formatted',
                        'creator',
                        'company_name',
                        'company_number',
                        'status',
                        'company',
                    ])
                    ->respond()
                    ->getData(true)
            );
    }

    public function test_admin_get_edaat_invoices_controller_filltered_by_invoice_number_and_company_id()
    {
        $edaatInvoice = EdaatInvoice::where('company_id', self::$secondCompany->id)
            ->first();

        $edaatInvoices = EdaatInvoice::with(['company', 'creator'])
            ->where('invoice_number', $edaatInvoice->invoice_number)
            ->where('company_id', self::$secondCompany->id)
            ->paginate();

        $this->actingAs(self::$admin)
            ->getJson('api/v1/admin/edaat-invoices?invoice_number='.$edaatInvoice->invoice_number.'&company_id='.self::$secondCompany->id)
            ->assertStatus(Response::HTTP_OK)
            ->assertExactJson(
                fractal($edaatInvoices, new EdaatInvoiceTransformer())
                    ->parseIncludes([
                        'id',
                        'invoice_number',
                        'amount',
                        'amount_formatted',
                        'creator',
                        'company_name',
                        'company_number',
                        'status',
                        'company',
                    ])
                    ->respond()
                    ->getData(true)
            );
    }

    public function test_admin_get_edaat_invoices_controller_validation_skip_wrong_company_id()
    {
        $edaatInvoices = EdaatInvoice::with(['company', 'creator'])
            ->paginate();

        $this->actingAs(self::$admin)
            ->getJson('api/v1/admin/edaat-invoices?company_id='. 50)
            ->assertStatus(Response::HTTP_OK)
            ->assertExactJson(
                fractal($edaatInvoices, new EdaatInvoiceTransformer())
                    ->parseIncludes([
                        'id',
                        'invoice_number',
                        'amount',
                        'amount_formatted',
                        'creator',
                        'company_name',
                        'company_number',
                        'status',
                        'company',
                    ])
                    ->respond()
                    ->getData(true)
            );
    }

    public function test_admin_get_edaat_invoices_controller_validation_skip_wrong_invoice_number()
    {
        $edaatInvoices = EdaatInvoice::with(['company', 'creator'])
            ->paginate();

        $bigText = Str::uuid().Str::uuid().Str::uuid().Str::uuid().Str::uuid();

        $this->actingAs(self::$admin)
            ->getJson('api/v1/admin/edaat-invoices?invlice_number='.$bigText)
            ->assertStatus(Response::HTTP_OK)
            ->assertExactJson(
                fractal($edaatInvoices, new EdaatInvoiceTransformer())
                    ->parseIncludes([
                        'id',
                        'invoice_number',
                        'amount',
                        'amount_formatted',
                        'creator',
                        'company_name',
                        'company_number',
                        'status',
                        'company',
                    ])
                    ->respond()
                    ->getData(true)
            );
    }

    public function test_admin_get_edaat_invoices_controller_lender_roles_can_not_access()
    {
        $this->assertStatusCodeForAllRolesExceptForArea(403, [Area::SuperAdmin, Area::Customer], function ($user, $role) {
            return $this->actingAs($user)
                ->getJson('api/v1/admin/edaat-invoices');
        });
    }

    public function test_admin_get_edaat_invoices_controller_manager_can_not_access_with_no_permission()
    {
        $this->actingAs(self::$manager)
            ->getJson('api/v1/admin/edaat-invoices')
            ->assertStatus(403);
    }

    public function test_admin_get_edaat_invoices_controller_manager_can_access_when_has_permission()
    {
        $this->actingAs(self::$managerHasPermission)
            ->getJson('api/v1/admin/edaat-invoices')
            ->assertStatus(200);
    }
}
