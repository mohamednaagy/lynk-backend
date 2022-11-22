<?php

namespace Tests\Feature\Endpoints\Api\V1\Lender\Order;

use App\Enums\ErrorCode;
use App\Enums\FinancingOrderStatus;
use App\Enums\Role;
use App\Models\Company;
use App\Models\FinancingOrder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Modules\Grantify\Facades\Grantify;
use Tests\TestCase;

class RejectOrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_order_rejected_successfully()
    {
        $campany = Company::factory()->create();
        $order = FinancingOrder::factory()->create(['company_id' => $campany->id, 'created_at' => now(), 'status' => FinancingOrderStatus::PendingApproval]);
        $statusReason = Str::random(80);
        $this->lenderLogin(Role::LenderAdmin, null, $campany);

        $response = $this->putJson('/api/v1/lender/orders/'.$order->id.'/reject', ['status_reason' => $statusReason], ['X-Company' => $campany->id]);
        $response->assertStatus(200);
    }

    public function test_order_can_not_moved_to_reject_status()
    {
        $campany = Company::factory()->create();
        $order = FinancingOrder::factory()->create(['company_id' => $campany->id, 'created_at' => now(), 'status' => FinancingOrderStatus::Approved]);

        $this->lenderLogin(Role::LenderAdmin, null, $campany);

        $response = $this->putJson('/api/v1/lender/orders/'.$order->id.'/reject', [], ['X-Company' => $campany->id]);
        $response->assertStatus(400)->assertJsonFragment([
            'message' => __('error.order_cannot_be_approved_because_it_is_approved'),
        ]);
    }

    public function test_other_company_can_not_reject_order()
    {
        $campany = Company::factory()->create();
        $order = FinancingOrder::factory()->create(['company_id' => $campany->id, 'created_at' => now(), 'status' => FinancingOrderStatus::Approved]);
        $otherCampany = Company::factory()->create();

        $this->lenderLogin(Role::LenderAdmin, null, $otherCampany);

        $response = $this->putJson('/api/v1/lender/orders/'.$order->id.'/reject', [], ['X-Company' => $otherCampany->id]);
        $response->assertStatus(404);
    }

    public function test_order_not_exists()
    {
        $campany = Company::factory()->create();

        $this->lenderLogin(Role::LenderAdmin, null, $campany);

        $response = $this->putJson('/api/v1/lender/orders/'. 1 .'/reject', [], ['X-Company' => $campany->id]);
        $response->assertStatus(404);
    }

    public function test_customer_can_not_reject_order()
    {
        $campany = Company::factory()->create();
        $order = FinancingOrder::factory()->create(['company_id' => $campany->id, 'created_at' => now(), 'status' => FinancingOrderStatus::PendingApproval]);

        $this->lenderLogin(Role::Customer, null, $campany);

        $response = $this->putJson('/api/v1/lender/orders/'.$order->id.'/reject', [], ['X-Company' => $campany->id]);
        $response->assertStatus(403);
    }

    public function test_can_not_reject_order_without_email_verification()
    {
        $campany = Company::factory()->create();
        $order = FinancingOrder::factory()->create(['company_id' => $campany->id, 'created_at' => now(), 'status' => FinancingOrderStatus::PendingApproval]);

        $user = User::factory()->create(['company_id' => $campany->id, 'email_verified_at' => null]);
        Grantify::assignRoleToModel($user, Role::LenderAdmin);
        $this->actingAs($user);

        $response = $this->putJson('/api/v1/lender/orders/'.$order->id.'/reject', [], ['X-Company' => $campany->id]);
        $response->assertStatus(403)->assertJsonFragment([
            'message' => __('error.must_verify_email'),
            'code' => ErrorCode::EMAIL_NOT_VERIFIED,
        ]);
    }
}
