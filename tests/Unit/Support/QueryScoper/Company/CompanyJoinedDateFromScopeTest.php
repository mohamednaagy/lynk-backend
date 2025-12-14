<?php

namespace Tests\Unit\Support\QueryScoper\Company;

use App\Models\Company;
use App\Models\Lender;
use App\Support\QueryScoper\Scopes\Company\CompanyJoinedDateFromScope;
use Tests\TestCase;

class CompanyJoinedDateFromScopeTest extends TestCase
{
    public function test_filters_by_created_at_from_date(): void
    {
        $early = Company::factory()->create(['created_at' => now()->subDays(10)]);
        $middle = Company::factory()->create(['created_at' => now()->subDays(5)]);
        $late = Company::factory()->create(['created_at' => now()]);

        request()->query->set('from_date', now()->subDays(7)->format('Y-m-d'));

        $builder = (new CompanyJoinedDateFromScope)->apply(Lender::query());
        $ids = $builder->pluck('id')->all();

        $this->assertNotContains($early->id, $ids);
        $this->assertContains($middle->id, $ids);
        $this->assertContains($late->id, $ids);
    }
}
