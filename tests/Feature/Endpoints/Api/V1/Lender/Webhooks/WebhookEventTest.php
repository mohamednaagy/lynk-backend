<?php

namespace Tests\Feature\Endpoints\Api\V1\Lender\Webhooks;

use App\Enums\FinancingOrderStatus;
use App\Enums\WebhookType;
use App\Models\Company;
use App\Models\User;
use App\Models\Webhook;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Config;
use Spatie\WebhookClient\Models\WebhookCall;
use Tests\TestCase;
use Tests\Traits\InteractsWithCompany;
use Tests\Traits\InteractsWithUser;

class WebhookEventTest extends TestCase
{
    use RefreshDatabase, InteractsWithCompany, InteractsWithUser;

    private static Company $company;

    private static User $userLender;

    private static Builder|Model $financingOrder;

    private static Webhook $webhook;

    public function setUp(): void
    {
        parent::setUp();

        [self::$company] = $this->createCompany(
            '2000',
            [
                'company_cr' => '12345678910',
                'webhook_secret_key' => 'secret_key',
            ]
        );
        self::$company->webhooks()->create([
            'url' => 'http://127.0.0.1:8000/api/webhook-receiving-url',
            'type' => WebhookType::OrderUpdates,
        ]);
        self::$userLender = $this->createLenderUser(self::$company->id);
        self::$financingOrder = $this->createOrder(self::$company->id, self::$userLender->id, [
            'status' => FinancingOrderStatus::ContractSigned,
        ]);

        self::$webhook = self::$company->webhooks()->first();
    }

    public function test_success_webhook_call()
    {
        Bus::fake();

        Config::set('webhook-client.configs.0.signing_secret', self::$company->webhook_secret_key);

        self::$financingOrder->update([
            'status' => FinancingOrderStatus::MurabhaOfferIssued,
        ]);

        $this->assertDatabaseCount(WebhookCall::class, 1);
    }
}
