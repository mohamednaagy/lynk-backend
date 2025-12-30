<?php

namespace App\Jobs\FinancingOrders;

use App\Actions\Contracts\GetSettingsClassInstance;
use App\Enums\Action;
use App\Enums\Area;
use App\Enums\GlobalNewOrderNotificationForAdminStatus;
use App\Enums\Role;
use App\Enums\Subject;
use App\Enums\SystemNotificationType;
use App\Models\FinancingOrder;
use App\Models\User;
use App\Notifications\FinancingOrders\OrderCreated;
use App\Services\NotificationPreferenceService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Notification;

class NotifyAdminsAboutOrderCreated implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $getSettingsClassInstance;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(private FinancingOrder $financingOrder, private User $user)
    {
        $this->onQueue('notifications');
        $this->getSettingsClassInstance = app(GetSettingsClassInstance::class);
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        if (! $this->isNotifyAllowed()) {
            return;
        }

        $notifiables = app(NotificationPreferenceService::class)
            ->getEnabledUsersFor(SystemNotificationType::ORDER_REQUIRES_APPROVAL, function ($query) {
                $query->where(function ($query) {
                    $query->role(Role::Admin)
                        ->orWhere(function ($query) {
                            $query->role(Role::Manager)
                                ->permission(
                                    perm(Area::SuperAdmin, [Subject::FinancingOrders, Action::Edit])
                                );
                        });
                });
            });

        Notification::send($notifiables, new OrderCreated($this->financingOrder, $this->user));
    }

    public function isNotifyAllowed()
    {
        $setting = app(GetSettingsClassInstance::class)
            ->handle(Area::Lender);

        $isNotificationSettingBasedOnCompany = $setting
            ->notify_admins_about_new_orders
            ->is(GlobalNewOrderNotificationForAdminStatus::BasedOnCompanySettings);

        if ($isNotificationSettingBasedOnCompany) {
            return (bool) $this->financingOrder
                ->lender
                ->lenderDetail
                ->notify_admins_about_new_orders
                ->value;
        }

        return $setting->notify_admins_about_new_orders
            ->is(GlobalNewOrderNotificationForAdminStatus::On)
            ? true
            : false;
    }
}
