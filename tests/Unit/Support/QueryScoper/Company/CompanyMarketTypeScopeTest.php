<?php

namespace Tests\Unit\Support\QueryScoper\Company;

use App\Enums\CompanyMarketType;
use App\Models\Company;
use App\Models\CompanyLenderDetail;
use App\Models\Lender;
use App\Support\QueryScoper\Scopes\Company\CompanyMarketTypeScope;
use Tests\TestCase;

class CompanyMarketTypeScopeTest extends TestCase
{
    public function test_filters_by_single_market_type(): void
    {
        $local = Company::factory()->create();
        CompanyLenderDetail::factory()->create([
            'company_id' => $local->id,
            'preferred_market_type' => CompanyMarketType::Local,
        ]);

        $intl = Company::factory()->create();
        CompanyLenderDetail::factory()->create([
            'company_id' => $intl->id,
            'preferred_market_type' => CompanyMarketType::International,
        ]);

        request()->query->set('market_type', CompanyMarketType::Local->value);

        $builder = (new CompanyMarketTypeScope)->apply(Lender::query());
        $ids = $builder->pluck('id')->all();

        $this->assertContains($local->id, $ids);
        $this->assertNotContains($intl->id, $ids);
    }

    public function test_filters_by_multiple_market_types(): void
    {
        $any = Company::factory()->create();
        CompanyLenderDetail::factory()->create([
            'company_id' => $any->id,
            'preferred_market_type' => CompanyMarketType::Any,
        ]);

        $intl = Company::factory()->create();
        CompanyLenderDetail::factory()->create([
            'company_id' => $intl->id,
            'preferred_market_type' => CompanyMarketType::International,
        ]);

        request()->query->set('market_type', [CompanyMarketType::Any->value, CompanyMarketType::International->value]);

        $builder = (new CompanyMarketTypeScope)->apply(Lender::query());
        $ids = $builder->pluck('id')->all();

        $this->assertContains($any->id, $ids);
        $this->assertContains($intl->id, $ids);
    }
}
