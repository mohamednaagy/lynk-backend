<?php

namespace Tests\Feature\Endpoints\Api\V1\Admin\Wallets;

use App\Enums\Action;
use App\Enums\Area;
use App\Enums\Role;
use App\Enums\Subject;
use App\Enums\WalletType;
use App\Models\Company;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;
use Tests\Traits\InteractsWithAdmin;
use Tests\Traits\InteractsWithLender;
use Tests\Traits\UsersInteractsWithRoute;

class ChargeLenderBalanceManuallyTest extends TestCase
{
    use RefreshDatabase;
    use InteractsWithLender;
    use InteractsWithAdmin;
    use UsersInteractsWithRoute;

    private static Company $company;

    private static Wallet $wallet;

    private static User $userLenderAdmin;

    private static User $admin;

    private static User $manager;

    private static User $managerHasPermission;

    private static User $lenderAdmin;

    private static User $lenderBilling;

    private static User $lenderApiUser;

    private static User $lenderOrderCreator;

    private static User $lenderSupervisor;

    /**
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        [self::$company, self::$wallet] = $this->createCompany('2000', ['company_cr' => '12345678910']);
        self::$userLenderAdmin = $this->createLenderUser(self::$company->id, Role::LenderAdmin, 'lenderAdmin@bim.com');
        self::$admin = $this->createAdmin();
        self::$manager = $this->createManager();
        self::$managerHasPermission = $this->createManager('managerHasPermission@bim.com', perm(Area::SuperAdmin, [Subject::LenderWallet, Action::Charge]));
        self::$lenderAdmin = $this->createLenderUser(self::$company->id, Role::LenderAdmin, 'LenderAdmin@bim.com');
        self::$lenderBilling = $this->createLenderUser(self::$company->id, Role::LenderBilling, 'LenderBilling@bim.com');
        self::$lenderApiUser = $this->createLenderUser(self::$company->id, Role::LenderApiUser, 'LenderApiUser@bim.com');
        self::$lenderOrderCreator = $this->createLenderUser(self::$company->id, Role::LenderOrderCreator, 'LenderOrderCreator@bim.com');
        self::$lenderSupervisor = $this->createLenderUser(self::$company->id, Role::LenderSupervisor, 'LenderSupervisor@bim.com');
    }

    public function test_charge_Lender_balance_manually_controller_validation_rules()
    {
        $this->actingAs(self::$admin)
            ->postJson('api/v1/admin/companies/'.self::$company->id.'/wallet/manual-deposit')
            ->assertStatus(422)
            ->assertJsonFragment([
                'message' => 'The amount field is required. (and 3 more errors)',
                'errors' => [
                    'amount' => [
                        0 => 'The amount field is required.',
                    ],
                    'description_en' => [
                        0 => 'The description en field is required.',
                    ],
                    'description_ar' => [
                        0 => 'The description ar field is required.',
                    ],
                    'attachment' => [
                        0 => 'The attachment field is required.',
                    ],
                ],
            ]);
    }

    public function test_charge_Lender_balance_manually_controller_successed()
    {
        $this->actingAs(self::$admin)
            ->postJson('api/v1/admin/companies/'.self::$company->id.'/wallet/manual-deposit', [
                'amount' => 10,
                'description_en' => 'deposit some money',
                'description_ar' => 'deposit some money',
                'attachment' => UploadedFile::fake()
                    ->create('attachment.pdf', 10),
            ])
            ->assertStatus(200)
            ->assertJsonFragment([
                'data' => [],
            ]);
    }

    public function test_charge_Lender_balance_manually_controller_transaction_description()
    {
        $this->actingAs(self::$admin);
        $this->postJson('api/v1/admin/companies/'.self::$company->id.'/wallet/manual-deposit', [
            'amount' => 50,
            'description_en' => 'deposit some money',
            'description_ar' => 'deposit some money',
            'attachment' => UploadedFile::fake()
                ->create('attachment.pdf'),
        ]);

        $response = $this->getJson('api/v1/admin/companies/'.self::$company->id.'/transactions');
        $this->assertTrue($response->getOriginalContent()->data[1]->description == __('transaction-description.manual_deposit'));
    }

    public function test_charge_Lender_balance_manually_controller_check_wallet_before_and_after_charge()
    {
        $balance = self::$company->balance(WalletType::CompanyWallet);
        $this->actingAs(self::$admin);
        $this->postJson('api/v1/admin/companies/'.self::$company->id.'/wallet/manual-deposit', [
            'amount' => 50,
            'description_en' => 'deposit some money',
            'description_ar' => 'deposit some money',
            'attachment' => UploadedFile::fake()
                ->create('attachment.pdf'),
        ]);

        $balanceAfterDeposit = self::$company->balance(WalletType::CompanyWallet);
        $this->assertTrue($balance->add(money(50, 'SAR', true))->equals($balanceAfterDeposit));
    }

    public function test_charge_Lender_balance_manually_admin_can_access()
    {
        $this->actingAs(self::$admin)
            ->postJson('api/v1/admin/companies/'.self::$company->id.'/wallet/manual-deposit', [
                'amount' => 10,
                'description_en' => 'deposit some money',
                'description_ar' => 'deposit some money',
                'attachment' => UploadedFile::fake()
                    ->create('attachment.pdf', 10),
            ])
            ->assertStatus(200);
    }

    public function test_charge_Lender_balance_manually_manager_can_not_access_with_no_permission()
    {
        $this->actingAs(self::$manager)
            ->postJson('api/v1/admin/companies/'.self::$company->id.'/wallet/manual-deposit', [
                'amount' => 10,
                'description_en' => 'deposit some money',
                'description_ar' => 'deposit some money',
                'attachment' => UploadedFile::fake()
                    ->create('attachment.pdf', 10),
            ])
            ->assertStatus(403);
    }

    public function test_charge_Lender_balance_manually_manager_can_access_when_has_permission()
    {
        $this->actingAs(self::$managerHasPermission)
            ->postJson('api/v1/admin/companies/'.self::$company->id.'/wallet/manual-deposit', [
                'amount' => 10,
                'description_en' => 'deposit some money',
                'description_ar' => 'deposit some money',
                'attachment' => UploadedFile::fake()
                    ->create('attachment.pdf', 10),
            ])
            ->assertStatus(200);
    }

    public function test_charge_Lender_balance_manually_other_roles_can_not_access()
    {
        $this->assertUsersStatusToPostRoute(
            'api/v1/admin/companies/'.self::$company->id.'/wallet/manual-deposit',
            403,
            [
                self::$lenderOrderCreator,
                self::$lenderApiUser,
                self::$lenderSupervisor,
                self::$lenderBilling,

            ],
            [
                'amount' => 10,
                'description_en' => 'deposit some money',
                'description_ar' => 'deposit some money',
                'attachment' => UploadedFile::fake()
                    ->create('attachment.pdf', 10),
            ]
        );
    }
}
