<?php

namespace Tests\Unit\Actions\Lender\Orders;

use App\Actions\Lenders\GetValidCommodityTypesAction;
use App\Enums\CommodityTypeStatus;
use App\Enums\CompanyMarketType;
use App\Enums\Trader;
use App\Enums\TraderOrderMode;
use App\Models\CommodityType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\InteractsWithCompany;

class GetValidCommodityTypesActionTest extends TestCase
{
    use InteractsWithCompany, RefreshDatabase;

    protected $company;

    protected function setUp(): void
    {
        parent::setUp();

        // Create the company globally for all tests
        $this->company = $this->createCompanyWithoutWallet();
        CommodityType::query()->delete(); // deleted the already seeded commodity types
    }

    /** @test */
    public function it_returns_empty_array_if_no_lender()
    {
        $this->company->update(['lender_id' => null]);

        $action = new GetValidCommodityTypesAction;
        $result = $action->handle($this->company);

        $this->assertEmpty($result);
    }

    /** @test */
    public function it_returns_empty_array_if_lender_detail_not_allowing_preferred_commodity()
    {
        $this->company->lender->lenderDetail()->create([
            'allow_preferred_commodity_in_order' => false,
        ]);

        $action = new GetValidCommodityTypesAction;
        $result = $action->handle($this->company);

        $this->assertEmpty($result);
    }

    /** @test */
    public function it_returns_correct_commodities_for_automatic_local()
    {
        $this->company->lender->lenderDetail()->create([
            'allow_preferred_commodity_in_order' => true,
            'trading_mode' => TraderOrderMode::Automatic,
            'preferred_market_type' => CompanyMarketType::Local,
        ]);

        $validCommodity = CommodityType::factory()->create([
            'provider' => Trader::Lynk,
            'status' => CommodityTypeStatus::Active,
        ]);

        CommodityType::factory()->create([
            'provider' => Trader::Bursam,
            'status' => CommodityTypeStatus::Active,
        ]);

        $action = new GetValidCommodityTypesAction;
        $result = $action->handle($this->company);

        $this->assertCount(1, $result);
        $this->assertEquals($validCommodity->unique_name, $result[0]['id']);
    }

    /** @test */
    public function it_returns_correct_commodities_for_automatic_international()
    {
        CommodityType::query()->delete();
        $this->company->lender->lenderDetail()->create([
            'allow_preferred_commodity_in_order' => true,
            'trading_mode' => TraderOrderMode::Automatic,
            'preferred_market_type' => CompanyMarketType::International,
        ]);

        $validCommodity = CommodityType::factory()->create([
            'provider' => Trader::Bursam,
            'status' => CommodityTypeStatus::Active,
        ]);

        CommodityType::factory()->create([
            'provider' => Trader::Lynk,
            'status' => CommodityTypeStatus::Active,
        ]);

        $action = new GetValidCommodityTypesAction;
        $result = $action->handle($this->company);

        $this->assertCount(1, $result);
        $this->assertEquals($validCommodity->unique_name, $result[0]['id']);
    }

    /** @test */
    public function it_returns_call_active_commodities_for_automatic_any()
    {
        CommodityType::query()->delete();
        $this->company->lender->lenderDetail()->create([
            'allow_preferred_commodity_in_order' => true,
            'trading_mode' => TraderOrderMode::Automatic,
            'preferred_market_type' => CompanyMarketType::Any,
        ]);

        $commodityOne = CommodityType::factory()->create([
            'provider' => Trader::Bursam,
            'status' => CommodityTypeStatus::Active,
        ]);

        $commodityTwo = CommodityType::factory()->create([
            'provider' => Trader::Lynk,
            'status' => CommodityTypeStatus::Active,
        ]);

        $action = new GetValidCommodityTypesAction;
        $result = $action->handle($this->company);

        $this->assertCount(2, $result);
        $this->assertEqualsCanonicalizing(
            [$commodityOne->unique_name, $commodityTwo->unique_name],
            array_column($result, 'id')
        );
    }

    /** @test */
    public function it_returns_all_active_commodities_for_manual_mode()
    {
        $this->company->lender->lenderDetail()->create([
            'allow_preferred_commodity_in_order' => true,
            'trading_mode' => TraderOrderMode::Manual,
            'preferred_market_type' => CompanyMarketType::Any,
        ]);

        $commodityOne = CommodityType::factory()->create([
            'provider' => Trader::Lynk,
            'status' => CommodityTypeStatus::Active,
        ]);

        $commodityTwo = CommodityType::factory()->create([
            'provider' => Trader::Bursam,
            'status' => CommodityTypeStatus::Active,
        ]);

        $action = new GetValidCommodityTypesAction;
        $result = $action->handle($this->company);

        $this->assertCount(2, $result);
        $this->assertEqualsCanonicalizing(
            [$commodityOne->unique_name, $commodityTwo->unique_name],
            array_column($result, 'id')
        );
    }
}
