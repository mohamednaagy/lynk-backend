<?php

namespace Tests\Feature\Endpoints\Api\V1\Lender\CommodityTypes;

use App\Enums\CommodityTypeStatus;
use App\Enums\CompanyMarketType;
use App\Enums\Role;
use App\Enums\Trader;
use App\Enums\TraderOrderMode;
use App\Models\CommodityType;
use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Grantify\Facades\Grantify;
use Tests\TestCase;
use Tests\Traits\InteractsWithCompany;
use Tests\Traits\InteractsWithUser;

class GetValidCommodityTypesTest extends TestCase
{
    use InteractsWithCompany, InteractsWithUser, RefreshDatabase;

    private static Company $company;

    private static User $userLender;

    private static string $apiUrl = '/api/v1/lender/commodity-types/dropdown-list';

    protected function setUp(): void
    {
        parent::setUp();

        [self::$company] = $this->createCompany('2000', ['company_cr' => '1234567891']);
        self::$userLender = $this->createLenderUser(self::$company->id, Role::LenderAdmin);
        self::$company->lender->lenderDetail()->create([
            'allow_preferred_commodity_in_order' => true,
            'trading_mode' => TraderOrderMode::Automatic,
            'preferred_market_type' => CompanyMarketType::Any,
        ]);

        CommodityType::query()->delete(); // deleted the already seeded commodity types
    }

    public function test_returns_valid_commodity_types_for_authorized_lender(): void
    {
        $commodityOne = CommodityType::factory()->create([
            'provider' => Trader::Bursam,
            'status' => CommodityTypeStatus::Active,
        ]);

        $commodityTwo = CommodityType::factory()->create([
            'provider' => Trader::Lynk,
            'status' => CommodityTypeStatus::Active,
        ]);

        $response = $this->actingAs(self::$userLender)
            ->withHeader('X-Company', self::$company->getRawOriginal('id'))
            ->getJson(self::$apiUrl);

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data')
            ->assertJsonFragment(['id' => $commodityOne->unique_name])
            ->assertJsonFragment(['id' => $commodityTwo->unique_name]);
    }

    public function test_returns_400_if_x_company_header_is_missing(): void
    {
        $response = $this->actingAs(self::$userLender)
            ->getJson(self::$apiUrl);

        $response->assertStatus(400)
            ->assertJson([
                'message' => 'Invalid company',
            ]);
    }

    public function test_returns_400_if_lender_detail_not_allowing_preferred_commodity(): void
    {
        self::$company->lender->lenderDetail->update(['allow_preferred_commodity_in_order' => false]);

        $response = $this->actingAs(self::$userLender)
            ->withHeader('X-Company', self::$company->getRawOriginal('id'))
            ->getJson(self::$apiUrl);

        $response->assertStatus(400)
            ->assertJson([
                'message' => 'This action is not allowed for this company.',
            ]);
    }

    public function test_fails_if_user_email_is_not_verified(): void
    {
        self::$userLender->email_verified_at = null;
        self::$userLender->save();

        $response = $this->actingAs(self::$userLender)
            ->withHeader('X-Company', self::$company->getRawOriginal('id'))
            ->getJson(self::$apiUrl);

        $response->assertStatus(403)
            ->assertJson([
                'message' => __('error.must_verify_email'),
                'code' => 1008,
            ]);
    }

    public function test_fails_if_lender_user_has_no_permission(): void
    {
        Grantify::syncRoleToModel(self::$userLender, Role::Admin);

        $response = $this->actingAs(self::$userLender)
            ->withHeader('X-Company', self::$company->getRawOriginal('id'))
            ->getJson(self::$apiUrl);

        $response->assertStatus(403)
            ->assertJson([
                'message' => 'User does not have the right roles.',
            ]);
    }
}
