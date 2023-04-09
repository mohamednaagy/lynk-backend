<?php

namespace App\Support\Traders;

use App\Support\Traders\Contracts\TraderInterface;
use App\Support\Traders\Drivers\Bursam\Strategies\BursamV1Driver;
use App\Support\Traders\Drivers\Dmcc\Strategies\DmccV1Driver;
use App\Support\Traders\Drivers\Dmcc\Strategies\DmccV2Driver;
use App\Support\Traders\Drivers\Fake\Strategies\FakeV1Driver;
use Illuminate\Support\Manager;

class TraderManager extends Manager
{
    public function driver($driver = null, $version = null)
    {
        $driver = $driver ?: $this->getDefaultDriver();

        if ($version) {
            $driver = $driver.ucfirst($version);
        }

        if (is_null($version)) {
            $driver = $driver.ucfirst(config('trader.providers.'.$driver.'.latest'));
        }

        if (is_null($driver)) {
            throw new \InvalidArgumentException(sprintf(
                'Unable to resolve NULL driver for [%s].', static::class
            ));
        }

        // If the given driver has not been created before, we will create the instances
        // here and cache it, so we can return it next time very quickly. If there is
        // already a driver created by this name, we'll just return that instance.
        if (! isset($this->drivers[$driver])) {
            $this->drivers[$driver] = $this->createDriver($driver);
        }

        return $this->drivers[$driver];
    }

    public function getDefaultDriver()
    {
        return config('trader.default', 'dmcc');
    }

    public function createBursamV1Driver(): TraderInterface
    {
        return new BursamV1Driver();
    }

    public function createDmccV1Driver(): TraderInterface
    {
        return new DmccV1Driver();
    }

    public function createDmccV2Driver(): TraderInterface
    {
        return new DmccV2Driver();
    }

    public function createFakeV1Driver(): TraderInterface
    {
        return new FakeV1Driver();
    }
}
