<?php

namespace Tests\Feature\Endpoints\Api\V1\Admin\Wallets;

use App\Enums\Action;
use App\Enums\Area;
use App\Enums\MediaCollections\TransactionMediaCollection;
use App\Enums\Role;
use App\Enums\Subject;
use App\Enums\WalletType;
use App\Models\Company;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use Cknow\Money\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;
use Tests\Traits\AssertsAccessByRoleAndArea;

class ChargeLenderBalanceManuallyTest extends TestCase
{
    use AssertsAccessByRoleAndArea;
    use RefreshDatabase;

    private static Company $lender;

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

    public function setUp(): void
    {
        parent::setUp();

        self::$lender = $this->createLenderCompanyWithStandardOrderCost(data: ['company_cr' => '12345678910']);
        self::$userLenderAdmin = $this->createLenderUser(self::$lender->id);
        self::$managerHasPermission = $this->createSuperAdminUser(Role::Manager);
        $this->assignPermissionToUser(self::$managerHasPermission, perm(Area::SuperAdmin, [Subject::LenderWallet, Action::Charge]));

        self::$admin = $this->createSuperAdminUser();
        self::$manager = $this->createSuperAdminUser(Role::Manager);
        self::$lenderAdmin = $this->createLenderUser(self::$lender->id, Role::LenderAdmin);
        self::$lenderBilling = $this->createLenderUser(self::$lender->id, Role::LenderBilling);
        self::$lenderApiUser = $this->createLenderUser(self::$lender->id, Role::LenderApiUser);
        self::$lenderOrderCreator = $this->createLenderUser(self::$lender->id, Role::LenderOrderCreator);
        self::$lenderSupervisor = $this->createLenderUser(self::$lender->id, Role::LenderSupervisor);
    }

    public function test_charge_lender_balance_manually_controller_validation_rules()
    {
        $this->actingAs(self::$admin)
            ->postJson('api/v1/admin/lenders/'.self::$lender->id.'/wallet/manual-deposit')
            ->assertStatus(422)
            ->assertJsonValidationErrorFor('amount')
            ->assertJsonValidationErrorFor('description_en')
            ->assertJsonValidationErrorFor('description_ar')
            ->assertJsonValidationErrorFor('attachment');
    }

    public function test_charge_lender_balance_manually_controller_succeeded()
    {
        $this->actingAs(self::$admin)
            ->postJson('api/v1/admin/lenders/'.self::$lender->id.'/wallet/manual-deposit', [
                'amount' => 115000,
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

    public function test_charge_lender_balance_manually_controller_transaction_description()
    {
        $this->actingAs(self::$admin);
        $this->postJson('api/v1/admin/lenders/'.self::$lender->id.'/wallet/manual-deposit', [
            'amount' => 115000,
            'description_en' => 'deposit some money',
            'description_ar' => 'deposit some money',
            'attachment' => UploadedFile::fake()
                ->create('attachment.pdf'),
        ]);

        $response = $this->getJson('api/v1/admin/lenders/'.self::$lender->id.'/transactions');
        $this->assertTrue($response->getOriginalContent()->data[1]->description == __('transaction-description.manual_deposit'));
    }

    public function test_charge_lender_balance_manually_controller_check_wallet_before_and_after_charge()
    {
        $balance = self::$lender->balance(WalletType::CompanyWallet);
        $this->actingAs(self::$admin);
        $this->postJson('api/v1/admin/lenders/'.self::$lender->id.'/wallet/manual-deposit', [
            'amount' => 115000,
            'description_en' => 'deposit some money',
            'description_ar' => 'deposit some money',
            'attachment' => UploadedFile::fake()
                ->create('attachment.pdf'),
        ]);

        $balanceAfterDeposit = self::$lender->balance(WalletType::CompanyWallet);
        $this->assertTrue($balance->add(Money::parseByDecimal(115000, Money::getDefaultCurrency()))->equals($balanceAfterDeposit));
    }

    public function test_charge_lender_balance_manually_voucher_invoice_generated_successfully()
    {
        $this->actingAs(self::$admin)
            ->postJson('api/v1/admin/lenders/'.self::$lender->id.'/wallet/manual-deposit', [
                'amount' => 115000,
                'description_en' => 'deposit some money',
                'description_ar' => 'deposit some money',
                'attachment' => UploadedFile::fake()
                    ->create('attachment.pdf'),
            ]);

        $response = $this->getJson('api/v1/admin/lenders/'.self::$lender->id.'/transactions');

        $this->assertTrue(Transaction::find($response->json('data.1.id'))->hasMedia(TransactionMediaCollection::VoucherReceipt));
    }

    public function test_charge_lender_balance_manually_admin_can_access()
    {
        $this->actingAs(self::$admin)
            ->postJson('api/v1/admin/lenders/'.self::$lender->id.'/wallet/manual-deposit', [
                'amount' => 115000,
                'description_en' => 'deposit some money',
                'description_ar' => 'deposit some money',
                'attachment' => UploadedFile::fake()
                    ->create('attachment.pdf', 10),
            ])
            ->assertStatus(200);
    }

    public function test_charge_lender_balance_manually_manager_can_not_access_with_no_permission()
    {
        $this->actingAs(self::$manager)
            ->postJson('api/v1/admin/lenders/'.self::$lender->id.'/wallet/manual-deposit', [
                'amount' => 115000,
                'description_en' => 'deposit some money',
                'description_ar' => 'deposit some money',
                'attachment' => UploadedFile::fake()
                    ->create('attachment.pdf', 10),
            ])
            ->assertStatus(403);
    }

    public function test_charge_lender_balance_manually_manager_can_access_when_has_permission()
    {
        $this->actingAs(self::$managerHasPermission)
            ->postJson('api/v1/admin/lenders/'.self::$lender->id.'/wallet/manual-deposit', [
                'amount' => 115000,
                'description_en' => 'deposit some money',
                'description_ar' => 'deposit some money',
                'attachment' => UploadedFile::fake()
                    ->create('attachment.pdf', 10),
            ])
            ->assertStatus(200);
    }

    public function test_that_order_show_cannot_be_accessed_by_lender_users()
    {
        $this->assertStatusCodeForAllRolesExceptForArea(403, [Area::SuperAdmin], function (User $user, string $role) {
            return $this->actingAs($user)
                ->withHeader('X-Company', self::$lender->getOriginal('id'))
                ->postJson('api/v1/admin/lenders/'.self::$lender->id.'/wallet/manual-deposit');
        });
    }
}
