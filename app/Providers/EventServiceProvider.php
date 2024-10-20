<?php

namespace App\Providers;

use App\Events\TraderOrderCancelled;
use App\Listeners\RefundOrderCost;
use App\Models\CommodityItem;
use App\Models\CommodityType;
use App\Models\CompanySupplierDetail;
use App\Models\FinancingOrder;
use App\Models\LocalMarketInventory;
use App\Models\LocalMarketOrder;
use App\Models\TraderHistory;
use App\Models\TraderOrder;
use App\Models\Transaction;
use App\Models\User;
use App\Observers\CommodityItemObserver;
use App\Observers\CommoditySupplierObserver;
use App\Observers\CommodityTypeObserver;
use App\Observers\FinancingOrderObserver;
use App\Observers\LocalMarketInventoryObserver;
use App\Observers\LocalMarketOrderObserver;
use App\Observers\TraderHistoryObserver;
use App\Observers\TraderOrderObserver;
use App\Observers\TransactionObserver;
use App\Observers\UserObserver;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],
        TraderOrderCancelled::class => [
            RefundOrderCost::class,
        ],
    ];

    protected $observers = [
        FinancingOrder::class => [FinancingOrderObserver::class],
        TraderHistory::class => [TraderHistoryObserver::class],
        TraderOrder::class => [TraderOrderObserver::class],
        Transaction::class => [TransactionObserver::class],
        LocalMarketInventory::class => [LocalMarketInventoryObserver::class],
        CommodityItem::class => [CommodityItemObserver::class],
        LocalMarketOrder::class => [LocalMarketOrderObserver::class],
        CompanySupplierDetail::class => [CommoditySupplierObserver::class],
        CommodityType::class => [CommodityTypeObserver::class],
        User::class => [UserObserver::class],
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void {}

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
