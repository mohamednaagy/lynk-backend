<?php

namespace App\Mixin;

use Closure;
use Illuminate\Routing\UrlGenerator;
use InvalidArgumentException;

/**
 * @mixin UrlGenerator
 */
class SignedExternalRouteMixin
{
    public function signedExternalRoute(): Closure
    {
        return function ($externalUrl, $name, $parameters = [], $expiration = null, $absolute = true) {
            if (! filter_var($externalUrl, FILTER_VALIDATE_URL)) {
                throw new InvalidArgumentException('Provided url not correct');
            }

            $parsedUrl = parse_url($externalUrl);
            // check trusted domain
            if (! in_array($parsedUrl['host'], config('app.host_whitelist', []))) {
                throw new InvalidArgumentException('The domain not listed in whitelist');
            }

            $externalUrl = preg_replace_callback('/\{(.*?)(\?)?\}/', function ($m) use ($parameters) {
                return isset($parameters[$m[1]]) && $parameters[$m[1]] !== '' ? $parameters[$m[1]] : $m[0];
            }, $externalUrl);

            $signedRoute = $this->signedRoute($name, $parameters, $expiration, $absolute);
            $parsedSignedRoute = parse_url($signedRoute);

            return $externalUrl.'?'.$parsedSignedRoute['query'] ?? '';
        };
    }
}
