<?php

namespace Tests\Unit\Support\QueryScoper\Company;

use App\Models\Company;
use App\Models\Lender;
use App\Support\QueryScoper\Scopes\Company\CompanyJoinedDateToScope;
use Tests\TestCase;

class CompanyJoinedDateToScopeTest extends TestCase
{
    public function test_filters_by_created_at_to_date(): void
    {
        $early = Company::factory()->create(['created_at' => now()->subDays(10)]);
        $middle = Company::factory()->create(['created_at' => now()->subDays(5)]);
        $late = Company::factory()->create(['created_at' => now()]);

        request()->query->set('to_date', now()->subDays(7)->format('Y-m-d'));

        $builder = (new CompanyJoinedDateToScope)->apply(Lender::query());
        $ids = $builder->pluck('id')->all();

        $this->assertContains($early->id, $ids);
        $this->assertContains($middle->id, $ids);
        $this->assertNotContains($late->id, $ids);
    }
}
