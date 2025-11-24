<?php

namespace Tests\Unit\Support\QueryScoper\Company;

use App\Models\Company;
use App\Models\Lender;
use App\Support\QueryScoper\Scopes\Company\CompanyNameScope;
use Tests\TestCase;

class CompanyNameScopeTest extends TestCase
{
    public function test_filters_by_partial_name(): void
    {
        $a = Company::factory()->create(['name' => 'Alpha Trading']);
        $b = Company::factory()->create(['name' => 'Beta Auto']);
        $c = Company::factory()->create(['name' => 'Gamma LLC']);

        request()->query->set('name', 'a');

        $builder = (new CompanyNameScope)->apply(Lender::query());
        $ids = $builder->pluck('id')->all();

        $this->assertContains($a->id, $ids);
        $this->assertContains($c->id, $ids);
        $this->assertNotContains($b->id, $ids);
    }

    public function test_ignores_when_missing(): void
    {
        $a = Company::factory()->create(['name' => 'Alpha']);
        $b = Company::factory()->create(['name' => 'Beta']);

        request()->query->remove('name');

        $builder = (new CompanyNameScope)->apply(Lender::query());
        $ids = $builder->pluck('id')->all();

        $this->assertContains($a->id, $ids);
        $this->assertContains($b->id, $ids);
    }
}
