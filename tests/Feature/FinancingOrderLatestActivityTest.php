<?php

namespace Tests\Feature;

use App\Enums\FinancingOrderStatus;
use App\Models\Company;
use App\Models\FinancingOrder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinancingOrderLatestActivityTest extends TestCase
{
    use RefreshDatabase;

    protected Company $tenant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tenant = Company::factory()->create();
        tenancy()->initialize($this->tenant);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function latest_activity_is_set_on_financing_order_creation()
    {
        $order = FinancingOrder::factory()->create([
            'status' => FinancingOrderStatus::PendingApproval,
        ]);

        $expectedActivity = FinancingOrderStatus::fromValue(FinancingOrderStatus::PendingApproval)->description;

        $this->assertEquals($expectedActivity, $order->latest_activity);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function latest_activity_is_updated_on_financing_order_status_change()
    {
        $order = FinancingOrder::factory()->create([
            'status' => FinancingOrderStatus::PendingApproval,
        ]);

        $order->status = FinancingOrderStatus::Approved;
        $order->save();

        $expectedActivity = FinancingOrderStatus::fromValue(FinancingOrderStatus::Approved)->description;

        $this->assertEquals($expectedActivity, $order->fresh()->latest_activity);
    }
}
