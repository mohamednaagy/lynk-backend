<?php

namespace Tests\Feature\Endpoints\Api\V1\Lender\FinancingOrders;

use App\Enums\FinancingOrderTypeEnum;
use App\Enums\Role;
use App\Jobs\FinancingOrders\NotifyAboutOrderRequiresApproval;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use ReflectionClass;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;
use Tests\Traits\InteractsWithCompany;
use Tests\Traits\InteractsWithUser;

class FinancingOrderControllerDispatchNotifyAboutOrderRequiresApprovalJobTest extends TestCase
{
    use InteractsWithCompany;
    use InteractsWithUser;
    use RefreshDatabase;

    private const ENDPOINT = 'api/v1/lender/orders';

    private \App\Models\Company $company;

    private \App\Models\User $lenderAdmin;

    private array $orderDetails;

    protected function setUp(): void
    {
        parent::setUp();

        // Avoid running unrelated queued jobs triggered by company creation (sync queue in tests).
        Queue::fake();

        $this->truncateWalletTables();

        $this->company = $this->createLenderCompanyWithStandardOrderCost('11500000');
        $this->lenderAdmin = $this->createLenderUser($this->company->id, Role::LenderAdmin);

        $this->orderDetails = [
            'customer_name' => 'test customer',
            'national_id' => '1001280070',
            'amount' => '200',
            'selling_price' => '220',
            'phone_country_code' => 'SA',
            'phone_number' => '500112233',
            'is_verification_required' => true,
        ];
    }

    public function test_it_dispatches_notify_job_when_order_requires_approval(): void
    {
        DB::table('company_lender_details')->updateOrInsert(
            ['company_id' => $this->company->id],
            [
                'does_order_require_approval' => true,
                'allowed_financing_order_types' => json_encode([FinancingOrderTypeEnum::NormalLending]),
            ]
        );

        Queue::fake();

        $response = $this->actingAs($this->lenderAdmin)
            ->withHeader('X-Company', $this->company->id)
            ->postJson(self::ENDPOINT, $this->orderDetails)
            ->assertStatus(Response::HTTP_OK);

        $financingOrderId = (int) $response->json('data.id');

        Queue::assertPushedOn('notifications', NotifyAboutOrderRequiresApproval::class);

        Queue::assertPushed(NotifyAboutOrderRequiresApproval::class, function (NotifyAboutOrderRequiresApproval $job) use ($financingOrderId) {
            $jobOrderId = (int) $this->readPrivateProperty($job, 'financingOrderId');
            $jobUser = $this->readPrivateProperty($job, 'user');

            return $jobOrderId === $financingOrderId
                && $jobUser instanceof \App\Models\User
                && $jobUser->id === $this->lenderAdmin->id;
        });
    }

    public function test_it_does_not_dispatch_notify_job_when_order_does_not_require_approval(): void
    {
        DB::table('company_lender_details')->updateOrInsert(
            ['company_id' => $this->company->id],
            [
                'does_order_require_approval' => false,
                'allowed_financing_order_types' => json_encode([FinancingOrderTypeEnum::NormalLending]),
            ]
        );

        Queue::fake();

        $this->actingAs($this->lenderAdmin)
            ->withHeader('X-Company', $this->company->id)
            ->postJson(self::ENDPOINT, $this->orderDetails)
            ->assertStatus(Response::HTTP_OK);

        Queue::assertNotPushed(NotifyAboutOrderRequiresApproval::class);
    }

    private function readPrivateProperty(object $object, string $propertyName): mixed
    {
        $reflection = new ReflectionClass($object);
        $property = $reflection->getProperty($propertyName);
        $property->setAccessible(true);

        return $property->getValue($object);
    }

    private function truncateWalletTables(): void
    {
        $walletConnection = DB::connection(config('wallet.database.connection'));
        $walletConnection->statement('SET FOREIGN_KEY_CHECKS=0');
        $walletConnection->table('transfers')->truncate();
        $walletConnection->table('transactions')->truncate();
        $walletConnection->table('wallets')->truncate();
        $walletConnection->statement('SET FOREIGN_KEY_CHECKS=1');
    }
}
