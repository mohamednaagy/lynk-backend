<?php

namespace Endpoints\Api\V1\Lender\Wallet\Notification;

use App\Enums\Role;
use App\Enums\WalletType;
use App\Models\Company;
use App\Models\User;
use App\Models\WalletNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\AssertsAccessByRoleAndArea;

class WalletNotificationControlleDestroyTest extends TestCase
{
    use RefreshDatabase;
    use AssertsAccessByRoleAndArea;

    private static Company $company;

    private static User $userLenderAdmin;

    private static User $userBilling;

    private static WalletNotification $walletNotification;

    private static $url = 'api/v1/lender/wallet-notifications/';

    public function setUp(): void
    {
        parent::setUp();

        [self::$company] = $this->createCompany();
        self::$walletNotification = WalletNotification::factory()->for(self::$company)->for(self::$company->getWallet(WalletType::CompanyWallet))->create();
        self::$userLenderAdmin = $this->createLenderUser(self::$company->id, Role::LenderAdmin);
        self::$userBilling = $this->createLenderUser(self::$company->id, Role::LenderBilling);
    }

    public function test_delete_wallet_notification_success_when_user_is_lender_admin()
    {
        $this->actingAs(self::$userLenderAdmin)
            ->withHeader('X-Company', self::$company->id)
            ->deleteJson(self::$url.self::$walletNotification->id)
            ->assertOk();
    }

    public function test_delete_wallet_notification_success_when_user_is_lender_billing()
    {
        $this->actingAs(self::$userBilling)
            ->withHeader('X-Company', self::$company->id)
            ->deleteJson(self::$url.self::$walletNotification->id)
            ->assertOk();
    }

    public function test_delete_wallet_notification_only_lender_admin_and_lender_billing_can_access()
    {
        $rolesHasNoPermission = [Role::LenderSupervisor, Role::LenderOrderCreator, Role::LenderApiUser];

        $this->assertStatusCodeToSpecificRoles(403, $rolesHasNoPermission, function ($user, $role) {
            return $this->actingAs($user)
                ->withHeader('X-Company', self::$company->id)
                ->deleteJson(self::$url.self::$walletNotification->id);
        });
    }
}
