<?php

namespace Tests\Unit\Jobs;

use App\Exceptions\TraderException;
use App\Jobs\Dmcc\ProcessDmccCancelNotification;
use App\Jobs\Dmcc\ProcessDmccMpoSaleCompleteNotification;
use App\Jobs\Dmcc\ProcessDmccNotifications;
use App\Jobs\Dmcc\ProcessDmccPtpDocumentRetrievedOrder;
use App\Jobs\Dmcc\ProcessDmccPtpNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class ProcessDmccNotificationsUnitTest extends TestCase
{
    use RefreshDatabase;

    public function test_process_dmcc_ptp_notification_dispatched()
    {
        Bus::fake();
        Http::fake(function () {
            return Http::response([
                'Body' => [
                    'notification' => 'Action Required for Promise to Purchase',
                    'ttiId' => 252,
                    'id' => '3dc10552-e6d0-4776-ad16-8ab5efde260d',
                ],
            ], 200);
        });

        (new ProcessDmccNotifications())->handle();

        Bus::assertDispatched(ProcessDmccPtpNotification::class);
    }

    public function test_process_dmcc_mpo_notification_dispatched()
    {
        Bus::fake();
        Http::fake(function () {
            return Http::response([
                'Body' => [
                    'notification' => 'Action Required for Issue Murabaha Purchase Offer',
                    'ttiId' => 252,
                    'id' => '3dc10552-e6d0-4776-ad16-8ab5efde260d',
                ],
            ], 200);
        });

        (new ProcessDmccNotifications())->handle();

        Bus::assertDispatched(ProcessDmccPtpDocumentRetrievedOrder::class);
    }

    public function test_process_dmcc_mpo_sale_complete_notification_dispatched()
    {
        Bus::fake();
        Http::fake(function () {
            return Http::response([
                'Body' => [
                    'notification' => 'Tradeflow Transaction (Islamic) - Payment Settlement Required',
                    'ttiId' => 252,
                    'id' => '3dc10552-e6d0-4776-ad16-8ab5efde260d',
                ],
            ], 200);
        });

        (new ProcessDmccNotifications())->handle();

        Bus::assertDispatched(ProcessDmccMpoSaleCompleteNotification::class);
    }

    public function test_process_dmcc_mpo_sale_complete_notification_dispatched_when_payment_is_settleted()
    {
        Bus::fake();
        Http::fake(function () {
            return Http::response([
                'Body' => [
                    'notification' => 'Tradeflow Transaction (Islamic) - Payment Settlement Required',
                    'ttiId' => 252,
                    'id' => '3dc10552-e6d0-4776-ad16-8ab5efde260d',
                ],
            ], 200);
        });

        (new ProcessDmccNotifications())->handle();

        Bus::assertDispatched(ProcessDmccMpoSaleCompleteNotification::class);
    }

    public function test_process_dmcc_cancel_notification_dispatched()
    {
        Bus::fake();
        Http::fake(function () {
            return Http::response([
                'Body' => [
                    'notification' => 'Tradeflow Transaction (Islamic) Cancelled',
                    'ttiId' => 252,
                    'id' => '3dc10552-e6d0-4776-ad16-8ab5efde260d',
                ],
            ], 200);
        });

        (new ProcessDmccNotifications())->handle();

        Bus::assertDispatched(ProcessDmccCancelNotification::class);
    }

    public function test_process_dmcc_ptp_notification_error_logged()
    {
        $logCount = Activity::query()->count();
        $this->expectException(TraderException::class);
        Http::fake(function () {
            return Http::response([
                'Body' => [
                    'notification' => 'Action Required for Promise to Purchase',
                    'ttiId' => 252,
                    'id' => '3dc10552-e6d0-4776-ad16-8ab5efde260d',
                ],
            ], 500);
        });

        (new ProcessDmccNotifications())->handle();
        $this->assertDatabaseCount((new Activity())->getTable(), $logCount + 1);
    }

    public function test_process_dmcc_mpo_sale_complete_notification_error_logged()
    {
        $logCount = Activity::query()->count();
        $this->expectException(TraderException::class);
        Http::fake(function () {
            return Http::response([
                'Body' => [
                    'notification' => 'Murabaha Sale Completed',
                    'ttiId' => 252,
                    'id' => '3dc10552-e6d0-4776-ad16-8ab5efde260d',
                ],
            ], 500);
        });

        (new ProcessDmccNotifications())->handle();
        $this->assertDatabaseCount((new Activity())->getTable(), $logCount + 1);
    }
}
