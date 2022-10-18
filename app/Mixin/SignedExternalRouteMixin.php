<?php
namespace App\Mixin;

use Exception;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Config;

class SignedExternalRouteMixin
{
    public function signedExternalRoute()
    {
        return function ($externalUrl, $name, $parameters = [], $expiration = null, $absolute = true) {
            if (!filter_var($externalUrl, FILTER_VALIDATE_URL)) {
                throw new Exception('Provided url not correct');
            }

            $url = parse_url($externalUrl);
            // check trusted domain
            if (!in_array($url['host'], Config::get('app.domain_whitelist', []))) {
                throw new Exception('The domain not listed in whitelist');
            }

            foreach ($parameters as $key => $value) {
                $externalUrl = Str::replace("{{$key}}", $value, $externalUrl);
            }

            $sginedRoute = $this->signedRoute($name, $parameters, $expiration, $absolute);
            $sginedRouteParse = parse_url($sginedRoute);
            parse_str($sginedRouteParse['query'], $sginedRouteQuery);
            return $externalUrl . '?' . http_build_query($sginedRouteQuery);
        };
    }
}
