<?php

namespace Tests\Unit\Support\QueryScoper\Company;

use App\Enums\TraderOrderMode;
use App\Models\Company;
use App\Models\CompanyLenderDetail;
use App\Models\Lender;
use App\Support\QueryScoper\Scopes\Company\CompanyTradingModeScope;
use Tests\TestCase;

class CompanyTradingModeScopeTest extends TestCase
{
    public function test_filters_by_trading_mode(): void
    {
        $manualCompany = Company::factory()->create();
        CompanyLenderDetail::factory()->create([
            'company_id' => $manualCompany->id,
            'trading_mode' => TraderOrderMode::Manual,
        ]);

        $autoCompany = Company::factory()->create();
        CompanyLenderDetail::factory()->create([
            'company_id' => $autoCompany->id,
            'trading_mode' => TraderOrderMode::Automatic,
        ]);

        request()->query->set('trading_mode', TraderOrderMode::Manual);

        $builder = (new CompanyTradingModeScope)->apply(Lender::query());
        $ids = $builder->pluck('id')->all();

        $this->assertContains($manualCompany->id, $ids);
        $this->assertNotContains($autoCompany->id, $ids);
    }
}
