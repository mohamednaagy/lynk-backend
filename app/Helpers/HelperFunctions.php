<?php

use Illuminate\Support\Facades\URL;

if (!function_exists('validate_said')) {
    function validate_said($id_number)
    {
        $id = trim($id_number);
        if (!is_numeric($id)) {
            return -1;
        }
        if (strlen($id) !== 10) {
            return -1;
        }
        $type = substr($id, 0, 1);
        if ($type != 2 && $type != 1) {
            return -1;
        }
        $sum = 0;
        for ($i = 0 ; $i < 10 ; $i++) {
            if ($i % 2 == 0) {
                $ZFOdd = str_pad((substr($id, $i, 1) * 2), 2, '0', STR_PAD_LEFT);
                $sum += substr($ZFOdd, 0, 1) + substr($ZFOdd, 1, 1);
            } else {
                $sum += substr($id, $i, 1);
            }
        }
        return $sum % 10 ? -1 : $type;
    }
}

if (!function_exists('get_signature_from_url')) {
    function get_signature_from_url($url, $key = 'signature=')
    {
        return substr($url, (strpos($url, $key) + strlen($key)), strlen($url));
    }
}

if (!function_exists('build_frontend_url')) {
    function build_frontend_url($path = '', $paramas = [], $area = null)
    {
        $host = $area === null || !array_key_exists($area, $frontendUrls = config('external-url.front_end_urls'))
        ? get_trusted_frontend_url() : get_host_from_url($frontendUrls[$area]);

        return URL::formatScheme() . $host . '/' . trim($path, '/') . '?' . http_build_query($paramas);
    }
}

if (!function_exists('get_trusted_frontend_url')) {
    function get_trusted_frontend_url()
    {
        $baseUrl = request()->header('origin');

        if (is_null($baseUrl)) {
            $baseUrl = request()->header('referer');
        }

        if (is_null($baseUrl)) {
            $baseUrl = request()->server->get('HTTP_HOST');
        }

        $host = get_host_from_url($baseUrl);

        if (isset($host) && in_array($host, get_frontend_hosts_from_config())) {
            return $host;
        }

        return null;
    }
}

if (!function_exists('get_frontend_hosts_from_config')) {
    function get_frontend_hosts_from_config()
    {
        $hosts = [];

        foreach (config('external-url.front_end_urls') as  $value) {
            array_push($hosts, get_host_from_url($value));
        }

        return $hosts;
    }
}


if (!function_exists('get_host_from_url')) {
    function get_host_from_url($url)
    {
        $parsedBaseUrl = parse_url($url);

        if (isset($parsedBaseUrl['host'])) {
            return $parsedBaseUrl['host'];
        } elseif (isset($parsedBaseUrl['path'])) {
            // path is used in case url has no http:// part
            return $parsedBaseUrl['path'];
        }
    }
}

