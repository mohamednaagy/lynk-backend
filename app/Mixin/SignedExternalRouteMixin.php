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

            $externalUrl = preg_replace_callback('/:([a-zA-Z_]{1,})/', function ($matches) use ($parameters) {
                return isset($parameters[$matches[1]]) && $parameters[$matches[1]] !== '' ? $parameters[$matches[1]] : $matches[0];
            }, $externalUrl);

            $signedRoute = $this->signedRoute($name, $parameters, $expiration, $absolute);

            $parsedSignedRoute = parse_url($signedRoute);

            return $externalUrl.'?'.$parsedSignedRoute['query'] ?? '';
        };
    }
}
