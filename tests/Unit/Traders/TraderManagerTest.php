<?php

namespace Tests\Unit\Traders;

use App\Support\Traders\Contracts\TraderInterface;
use App\Support\Traders\Drivers\DmccDriver;
use App\Support\Traders\Drivers\FakeDriver;
use App\Support\Traders\TraderManager;
use Tests\TestCase;

class TraderManagerTest extends TestCase
{
    protected TraderManager $traderManager;

    public function setUp(): void
    {
        parent::setUp();
        $this->traderManager = new TraderManager($this->app);
    }

    public function test_trader_manager_default_driver()
    {
        config()->set('trader.default', 'dmcc');
        $this->assertEquals('dmcc', $this->traderManager->getDefaultDriver());

        config()->set('trader.default', 'fake');
        $this->assertEquals('fake', $this->traderManager->getDefaultDriver());
    }

    public function test_trader_manager_create_dmcc_driver()
    {
        $this->assertInstanceOf(TraderInterface::class, $this->traderManager->createDmccDriver());
        $this->assertInstanceOf(DmccDriver::class, $this->traderManager->createDmccDriver());
    }

    public function test_trader_manager_create_fake_driver()
    {
        $this->assertInstanceOf(TraderInterface::class, $this->traderManager->createFakeDriver());
        $this->assertInstanceOf(FakeDriver::class, $this->traderManager->createFakeDriver());
    }
}
