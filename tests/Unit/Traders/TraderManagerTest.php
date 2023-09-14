<?php

namespace Tests\Unit\Traders;

use App\Support\Traders\Contracts\TraderInterface;
use App\Support\Traders\Drivers\Bursam\Strategies\BursamV1Driver;
use App\Support\Traders\Drivers\Bursam\Strategies\BursamV2Driver;
use App\Support\Traders\Drivers\Dmcc\Strategies\DmccV1Driver;
use App\Support\Traders\Drivers\Fake\Strategies\FakeV1Driver;
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
        config()->set('trader.default', 'bursam');
        $this->assertEquals('bursam', $this->traderManager->getDefaultDriver());

        config()->set('trader.default', 'dmcc');
        $this->assertEquals('dmcc', $this->traderManager->getDefaultDriver());

        config()->set('trader.default', 'fake');
        $this->assertEquals('fake', $this->traderManager->getDefaultDriver());
    }

    public function test_trader_manager_create_bursam_v1_driver()
    {
        $this->assertInstanceOf(TraderInterface::class, $this->traderManager->createBursamV1Driver());
        $this->assertInstanceOf(BursamV1Driver::class, $this->traderManager->createBursamV1Driver());
    }

    public function test_trader_manager_create_bursam_v2_driver()
    {
        $this->assertInstanceOf(TraderInterface::class, $this->traderManager->createBursamV2Driver());
        $this->assertInstanceOf(BursamV2Driver::class, $this->traderManager->createBursamV2Driver());
    }

    public function test_trader_manager_create_dmcc_v1_driver()
    {
        $this->assertInstanceOf(TraderInterface::class, $this->traderManager->createDmccV1Driver());
        $this->assertInstanceOf(DmccV1Driver::class, $this->traderManager->createDmccV1Driver());
    }

    public function test_trader_manager_create_fake_v1_driver()
    {
        $this->assertInstanceOf(TraderInterface::class, $this->traderManager->createFakeV1Driver());
        $this->assertInstanceOf(FakeV1Driver::class, $this->traderManager->createFakeV1Driver());
    }
}
