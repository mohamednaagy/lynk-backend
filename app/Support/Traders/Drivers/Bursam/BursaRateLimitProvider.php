<?php

namespace App\Support\Traders\Drivers\Bursam;

use Concat\Http\Middleware\RateLimitProvider;
use Illuminate\Contracts\Cache\Repository as Cache;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class BursaRateLimitProvider implements RateLimitProvider
{
    protected float $requestTime;

    public function __construct(protected Cache $cache, protected $ttl = 1)
    {
    }

    public function getLastRequestTime(RequestInterface $request)
    {
        return $this->cache->get($this->getCacheKey($request), time() - $this->ttl + 1);
    }

    public function setLastRequestTime(RequestInterface $request): bool
    {
        return $this->cache->put($this->getCacheKey($request), $this->getRequestTime($request), $this->ttl);
    }

    public function getRequestTime(RequestInterface $request): int
    {
        return time();
    }

    public function getRequestAllowance(RequestInterface $request): int
    {
        return $this->ttl;
    }

    public function setRequestAllowance(ResponseInterface $response): void
    {
        //
    }

    protected function getCacheKey(RequestInterface $request): string
    {
        return 'bursa_rate_limit';
    }
}
