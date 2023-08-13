<?php

namespace App\Providers;

use App\Events\OrderCancelled;
use App\Listeners\RefundOrderCost;
use App\Models\FinancingOrder;
use App\Models\TraderHistory;
use App\Models\TraderOrder;
use App\Observers\FinancingOrderObserver;
use App\Observers\TraderHistoryObserver;
use App\Observers\TraderOrderObserver;
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
        OrderCancelled::class => [
            RefundOrderCost::class,
        ],
    ];

    protected $observers = [
        FinancingOrder::class => [FinancingOrderObserver::class],
        TraderHistory::class => [TraderHistoryObserver::class],
        TraderOrder::class => [TraderOrderObserver::class],
    ];

    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot(): void
    {
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     *
     * @return bool
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
