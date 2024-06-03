<?php

namespace App\Support\Traders;

use App\Support\Traders\Contracts\TraderInterface;
use App\Support\Traders\Drivers\Bursam\Strategies\BursamV1Driver;
use App\Support\Traders\Drivers\Bursam\Strategies\BursamV2Driver;
use App\Support\Traders\Drivers\Bursam\Strategies\LynkV1Driver;
use App\Support\Traders\Drivers\Dmcc\Strategies\DmccV1Driver;
use App\Support\Traders\Drivers\Fake\Strategies\FakeV1Driver;
use Illuminate\Support\Manager;

class Lynk extends Manager
{
    public function driver($provider = null, $version = null)
    {
        $provider = $provider ?: $this->getDefaultDriver();

        if (is_null($version)) {
            $provider = $provider.ucfirst(get_latest_version_of_trader($provider));
        } else {
            $provider = $provider.ucfirst($version);
        }

        // If the given driver has not been created before, we will create the instances
        // here and cache it, so we can return it next time very quickly. If there is
        // already a driver created by this name, we'll just return that instance.
        if (! isset($this->drivers[$provider])) {
            $this->drivers[$provider] = $this->createDriver($provider);
        }

        return $this->drivers[$provider];
    }

    public function getDefaultDriver()
    {
        return config('trader.default', 'dmcc');
    }

    public function createBursamV1Driver(): TraderInterface
    {
        return new BursamV1Driver();
    }

    public function createBursamV2Driver(): TraderInterface
    {
        return new BursamV2Driver();
    }

    public function createDmccV1Driver(): TraderInterface
    {
        return new DmccV1Driver();
    }

    public function createLynkV1Driver(): TraderInterface
    {
        return new LynkV1Driver();
    }

    public function createFakeV1Driver(): TraderInterface
    {
        return new FakeV1Driver();
    }
}
