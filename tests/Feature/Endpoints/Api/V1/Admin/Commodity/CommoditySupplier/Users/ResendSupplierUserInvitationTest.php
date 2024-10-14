<?php

namespace Endpoints\Api\V1\Admin\Commodity\CommoditySupplier\Users;

use App\Mail\Supplier\CompleteSupplierRegisterInvitation;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;
use Tests\Traits\AssertsAccessByRoleAndArea;
use Tests\Traits\InteractsWithCommoditySupplier;
use Tests\Traits\InteractsWithSupplier;

class ResendSupplierUserInvitationTest extends TestCase
{
    use AssertsAccessByRoleAndArea;
    use InteractsWithCommoditySupplier;
    use InteractsWithSupplier;
    use RefreshDatabase;

    private static User $superAdminNotJoined;

    private static User $lenderBelongsToCompanyNotActive;

    private static User $superAdmin;

    private static User $supervisor;

    private static User $supplierUserNotVerified;

    private static string $redirectUrl;

    private static Supplier $supplier;

    public function setUp(): void
    {
        parent::setUp();

        self::$redirectUrl = 'http://localhost:3000';
        self::$superAdmin = $this->createSuperAdminUser();
        self::$supervisor = $this->createSupervisorUser();

        self::$supplier = $this->createSupplier();
        self::$supplierUserNotVerified = $this->createSupplierUser(supplierId: self::$supplier->id, data: ['password' => null, 'email_verified_at' => null]);

    }

    public function test_resend_invitation_super_admin_can_access()
    {
        $this->actingAs(self::$superAdmin)
            ->postJson(
                'api/v1/admin/commodity-suppliers/'.self::$supplier->id.'/users/'.self::$supplierUserNotVerified->id.'/resend-invitation',
                [
                    'redirect_url' => self::$redirectUrl,
                ]
            )
            ->assertStatus(200);
    }

    public function test_resend_invitation_supervisor_can_access()
    {
        $this->actingAs(self::$supervisor)
            ->postJson(
                'api/v1/admin/commodity-suppliers/'.self::$supplier->id.'/users/'.self::$supplierUserNotVerified->id.'/resend-invitation',
                [
                    'redirect_url' => self::$redirectUrl,
                ]
            )
            ->assertStatus(403);
    }

    public function test_success_resend_invitation_email_is_sent_to_supplier_user()
    {
        Mail::fake();
        $this->actingAs(self::$superAdmin)
            ->postJson(
                'api/v1/admin/commodity-suppliers/'.self::$supplier->id.'/users/'.self::$supplierUserNotVerified->id.'/resend-invitation',
                [
                    'redirect_url' => self::$redirectUrl,
                ]
            )
            ->assertStatus(200);

        Mail::assertQueued(CompleteSupplierRegisterInvitation::class);

    }
}
