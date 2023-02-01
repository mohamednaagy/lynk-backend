<?php

namespace Tests\Unit\Jobs\Dmcc;

use App\Enums\FinancingOrderHistory;
use App\Enums\FinancingOrderStatus;
use App\Enums\MediaCollections\TraderOrderMediaCollection;
use App\Enums\Role;
use App\Enums\TraderOrderStatus;
use App\Jobs\Dmcc\ProcessDmccMpoNotification;
use App\Models\Company;
use App\Models\FinancingOrder;
use App\Models\Media;
use App\Models\TraderHistory;
use App\Models\TraderOrder;
use App\Models\User;
use CodeDredd\Soap\Facades\Soap;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use Tests\Traits\InteractsWithLender;

class ProcessDmccMpoNotificationUnitTest extends TestCase
{
    use RefreshDatabase, InteractsWithLender;

    protected static Company $company;

    protected static User $lender;

    protected static FinancingOrder $order;

    protected static Model|TraderOrder $traderOrder;

    protected static object $notification;

    /**
     * @throws BindingResolutionException
     */
    protected function setUp(): void
    {
        parent::setUp();

        [self::$company] = $this->createCompany();
        self::$lender = $this->createLenderUser(self::$company->id, Role::LenderAdmin);
        self::$order = $this->createOrder(self::$company->id, self::$lender->id, [
            'status' => FinancingOrderStatus::ClientWakalaCompleted,
        ]);
        self::$traderOrder = TraderOrder::query()->create([
            'financing_order_id' => self::$order->id,
            'reference' => 1,
            'provider' => 'dmcc',
            'status' => TraderOrderStatus::InProgress,
            'amount' => 1,
            'product' => 'product',
            'quantity' => 1,
            'warehouse' => 'warehouse',
            'owner' => 'owner',
        ]);
        self::$notification = (object) [
            'notificationHeaderAndEntity' => (object) [
                'notification' => 'Action Required for Issue Murabaha Purchase Offer',
                'notificationEntityDetails' => (object) [
                    'notificationEntity' => [
                        (object) [
                            'entityValue' => '1',
                        ],
                    ],
                ],
            ],
        ];
        Soap::fake(function () {
            return Soap::response([
                'successCode' => '0000',
                'versionNo' => 1,
                'getdocument' => [
                    [
                        'getDocumentByTypeResponse' => [
                            ['document' => 'document'],
                        ],
                    ],
                ],
            ]);
        });
    }

    public function test_process_dmcc_mpo_notification_with_dmcc_as_trader_will_success()
    {
        (new ProcessDmccMpoNotification(self::$notification))->handle();
        $this->assertTrue(self::$order->fresh()->status->is(FinancingOrderStatus::MurabhaOfferIssued));
    }

    public function test_process_dmcc_mpo_notification_with_fake_as_trader_order_will_success()
    {
        self::$traderOrder->update([
            'provider' => 'fake',
        ]);
        (new ProcessDmccMpoNotification(self::$notification))->handle();
        $this->assertTrue(self::$order->fresh()->status->is(FinancingOrderStatus::MurabhaOfferIssued));
    }

    public function test_process_dmcc_mpo_notification_with_not_supported_trader_will_fail()
    {
        self::$traderOrder->update([
            'provider' => 'else',
        ]);
        (new ProcessDmccMpoNotification(self::$notification))->handle();
        $this->assertTrue(self::$order->fresh()->status->is(FinancingOrderStatus::ClientWakalaCompleted));
    }

    public function test_process_dmcc_mpo_notification_when_order_status_not_client_wakala_complete_fail()
    {
        self::$order->update([
            'status' => FinancingOrderStatus::MurabhaOfferIssued,
        ]);
        (new ProcessDmccMpoNotification(self::$notification))->handle();
        $this->assertTrue(self::$order->fresh()->status->is(FinancingOrderStatus::MurabhaOfferIssued));
    }

    public function test_process_dmcc_mpo_notification_histories_created()
    {
        Storage::fake();
        UploadedFile::fake();

        $traderHistories = TraderHistory::query()->count();
        $media = Media::query()->count();

        (new ProcessDmccMpoNotification(self::$notification))->handle();

        $this->assertDatabaseCount((new TraderHistory())->getTable(), $traderHistories + 3);
        $this->assertDatabaseHas((new TraderHistory())->getTable(), [
            'trader_order_id' => self::$traderOrder->id,
            'action' => FinancingOrderHistory::IssueMurabahaOffer,
        ]);
        $this->assertDatabaseHas((new TraderHistory())->getTable(), [
            'trader_order_id' => self::$traderOrder->id,
            'action' => FinancingOrderHistory::GetMurabahaPurchaseOfferDocument,
        ]);
        $this->assertDatabaseHas((new TraderHistory())->getTable(), [
            'trader_order_id' => self::$traderOrder->id,
            'action' => FinancingOrderHistory::AttachMpoDocument,
        ]);

        $this->assertDatabaseCount((new Media())->getTable(), $media + 1);

        $this->assertDatabaseHas((new Media())->getTable(), [
            'model_id' => self::$traderOrder->id,
            'model_type' => (new TraderOrder)->getMorphClass(),
            'collection_name' => TraderOrderMediaCollection::MurabahaPurchaseOrder,
        ]);

        $this->assertNotNull(self::$traderOrder->getFirstMediaUrl(TraderOrderMediaCollection::MurabahaPurchaseOrder));
    }
}
