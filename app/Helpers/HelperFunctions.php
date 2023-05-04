<?php

use Illuminate\Support\Carbon;
use Modules\Grantify\Facades\Grantify;

if (! function_exists('validate_said')) {
    function validate_said($id_number)
    {
        $id = trim($id_number);
        if (! is_numeric($id)) {
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
        for ($i = 0; $i < 10; $i++) {
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

if (! function_exists('perm')) {
    function perm($areas, ...$permissions)
    {
        $permissionsArray = [];

        if (is_array($areas)) {
            foreach ($areas as $area) {
                foreach ($permissions as $subjectWithPermissions) {
                    $subject = $subjectWithPermissions[0];
                    unset($subjectWithPermissions[0]);
                    array_push($permissionsArray, Grantify::transformToPermissionsFormat($area, $subject, $subjectWithPermissions));
                }
            }

            return implode('|', $permissionsArray);
        }

        foreach ($permissions as $subjectWithPermissions) {
            $subject = $subjectWithPermissions[0];
            unset($subjectWithPermissions[0]);
            array_push($permissionsArray, Grantify::transformToPermissionsFormat($areas, $subject, $subjectWithPermissions));
        }

        return implode('|', $permissionsArray);
    }
}

if (! function_exists('perm_arr')) {
    function perm_arr($areas, ...$permissions)
    {
        return explode('|', perm($areas, ...$permissions));
    }
}

if (! function_exists('get_host_from_url')) {
    function get_host_from_url($url)
    {
        $url = parse_url($url, PHP_URL_HOST) ?: explode('/', parse_url($url, PHP_URL_PATH), 2);

        return is_array($url) ?
            array_shift($url)
            : $url;
    }
}

if (! function_exists('get_file_url')) {
    function get_file_url($media): ?string
    {
        if ($media) {
            return route('api.v1.media.download', ['media' => $media->uuid]);
        }

        return null;
    }
}

if (! function_exists('convert_date_timezone')) {
    function convert_date_timezone($date, $timezone, $old_timezone = 'UTC'): Carbon
    {
        return Carbon::parse($date, $old_timezone)->setTimezone($timezone);
    }
}
