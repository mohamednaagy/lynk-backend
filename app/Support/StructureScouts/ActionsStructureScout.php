<?php

namespace App\Support\StructureScouts;

use Spatie\StructureDiscoverer\Cache\DiscoverCacheDriver;
use Spatie\StructureDiscoverer\Cache\FileDiscoverCacheDriver;
use Spatie\StructureDiscoverer\Discover;
use Spatie\StructureDiscoverer\StructureScout;

class ActionsStructureScout extends StructureScout
{
    public function identifier(): string
    {
        return 'app-actions';
    }

    protected function definition(): Discover
    {
        return Discover::in(app_path('Actions'))
            ->interfaces();
    }

    public function cacheDriver(): DiscoverCacheDriver
    {
        return new FileDiscoverCacheDriver(base_path('bootstrap/cache'));
    }
}
