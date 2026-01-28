<?php

namespace Tests\Unit\Jobs\FinancingOrders;

use App\Enums\CompanyType;
use App\Enums\NotificationChannel;
use App\Enums\Role;
use App\Enums\SystemNotificationType;
use App\Jobs\FinancingOrders\NotifyAboutOrderRequiresApproval;
use App\Models\Company;
use App\Models\FinancingOrder;
use App\Models\User;
use App\Models\UserNotificationSetting;
use App\Notifications\FinancingOrders\OrderRequiresApproval;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;
use Tests\Traits\InteractsWithCompany;
use Tests\Traits\InteractsWithUser;

class NotifyAboutOrderRequiresApprovalTest extends TestCase
{
    use InteractsWithCompany;
    use InteractsWithUser;
    use RefreshDatabase;

    private Company $lenderCompany;

    private FinancingOrder $financingOrder;

    private User $orderCreator;

    protected function setUp(): void
    {
        parent::setUp();

        Queue::fake();

        $this->lenderCompany = $this->createCompanyWithoutWallet(['type' => CompanyType::Lender]);
        $this->orderCreator = $this->createLenderUser($this->lenderCompany->id, Role::LenderAdmin);
        $this->financingOrder = $this->createOrder($this->lenderCompany->id, $this->orderCreator->id);
    }

    public function test_it_sends_email_notification_only_to_users_with_mail_enabled(): void
    {
        Notification::fake();

        $admin = $this->createSuperAdminUser(Role::Admin);
        $lenderAdminSameLender = $this->createLenderUser($this->lenderCompany->id, Role::LenderAdmin);

        $otherLender = $this->createLenderCompanyWithStandardOrderCost('11500000');
        $lenderAdminOtherLender = $this->createLenderUser($otherLender->id, Role::LenderAdmin);

        $disabledAdmin = $this->createSuperAdminUser(Role::Admin);

        $this->enableMailOnly($admin);
        $this->enableMailOnly($lenderAdminSameLender);
        $this->enableMailOnly($lenderAdminOtherLender);
        $this->disableMailAndPlatform($disabledAdmin);

        $job = new NotifyAboutOrderRequiresApproval($this->financingOrder->id, $this->orderCreator);
        $job->handle();

        Notification::assertSentTo($admin, OrderRequiresApproval::class, function (OrderRequiresApproval $notification, array $channels) {
            return in_array(NotificationChannel::MAIL->value, $channels, true);
        });

        Notification::assertSentTo($lenderAdminSameLender, OrderRequiresApproval::class, function (OrderRequiresApproval $notification, array $channels) {
            return in_array(NotificationChannel::MAIL->value, $channels, true);
        });

        Notification::assertNotSentTo($lenderAdminOtherLender, OrderRequiresApproval::class);
        Notification::assertNotSentTo($disabledAdmin, OrderRequiresApproval::class);
    }

    public function test_it_does_not_send_when_financing_order_is_missing(): void
    {
        Notification::fake();

        $admin = $this->createSuperAdminUser(Role::Admin);
        $this->enableMailOnly($admin);

        $job = new NotifyAboutOrderRequiresApproval(999999999, $this->orderCreator);
        $job->handle();

        Notification::assertNothingSent();
    }

    private function enableMailOnly(User $user): void
    {
        UserNotificationSetting::query()->updateOrCreate(
            [
                'user_id' => $user->id,
                'notification_type' => SystemNotificationType::ORDER_REQUIRES_APPROVAL,
                'channel' => NotificationChannel::MAIL,
            ],
            ['is_enabled' => true]
        );

        UserNotificationSetting::query()->updateOrCreate(
            [
                'user_id' => $user->id,
                'notification_type' => SystemNotificationType::ORDER_REQUIRES_APPROVAL,
                'channel' => NotificationChannel::PLATFORM,
            ],
            ['is_enabled' => false]
        );
    }

    private function disableMailAndPlatform(User $user): void
    {
        UserNotificationSetting::query()->updateOrCreate(
            [
                'user_id' => $user->id,
                'notification_type' => SystemNotificationType::ORDER_REQUIRES_APPROVAL,
                'channel' => NotificationChannel::MAIL,
            ],
            ['is_enabled' => false]
        );

        UserNotificationSetting::query()->updateOrCreate(
            [
                'user_id' => $user->id,
                'notification_type' => SystemNotificationType::ORDER_REQUIRES_APPROVAL,
                'channel' => NotificationChannel::PLATFORM,
            ],
            ['is_enabled' => false]
        );
    }
}
