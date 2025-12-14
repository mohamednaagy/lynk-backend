<?php

namespace Tests\Unit\Models;

use App\Enums\FinancingOrderStatus;
use App\Enums\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\InteractsWithLender;

class FinancingOrderStatusHistoryTest extends TestCase
{
    use InteractsWithLender, RefreshDatabase;

    public function test_initial_status_history_is_created_on_order_creation(): void
    {
        [$company] = $this->createCompany('2000', ['company_cr' => '1234567891']);
        $user = $this->createLenderUser($company->id, Role::LenderAdmin);

        $order = $this->createOrder($company->id, $user->id, [
            'status' => FinancingOrderStatus::PendingApproval,
        ]);

        $this->assertEquals(1, $order->statusHistories()->count());
        $this->assertEquals(FinancingOrderStatus::PendingApproval, $order->latestStatusHistory->status);
        $this->assertEquals($user->id, $order->latestStatusHistory->creator_id);
    }
}
