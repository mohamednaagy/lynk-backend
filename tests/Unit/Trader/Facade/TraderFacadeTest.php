<?php

namespace Tests\Unit\Trader\Facade;

use App\Support\Traders\Facades\Trader;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TraderFacadeTest extends TestCase
{
    use RefreshDatabase;

    public function setUp(): void
    {
        parent::setUp();
    }

    /**
     * @return void
     */
    public function test_underlying_resolved_instance_TraderManager_class(): void
    {
        $this->assertInstanceOf('App\Support\Traders\TraderManager',
            Trader::getFacadeRoot()
        );
    }
}
