<?php

use App\Enums\MurabhaStep;
use App\Models\Media;
use Illuminate\Support\Facades\Config;
use Modules\Grantify\Facades\Grantify;
use Spatie\MediaLibrary\HasMedia;

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

if (! function_exists('get_media_of_model')) {
    function get_media_of_model($model, $mediaCollection): ?Media
    {
        if ($model instanceof HasMedia) {
            return $model->getFirstMedia($mediaCollection);
        }

        return null;
    }
}

if (! function_exists('get_latest_version_of_trader')) {
    function get_latest_version_of_trader($provider): string
    {
        return config('trader.providers.'.$provider.'.latest');
    }
}

if (! function_exists('get_murabha_steps')) {
    function get_murabha_steps($provider, string $version = null): array
    {
        return config('murabha-steps.'.$provider.'-versions.'.$version ?? get_latest_version_of_trader($provider));
    }
}

if (! function_exists('trader_step_histories')) {
    function trader_step_histories(string $provider, string $version): array
    {
        return MurabhaStep::getStepsOfVersion($provider, $version);
    }
}

if (! function_exists('is_bursam_service_available')) {
    function is_bursam_service_available(): bool
    {
        $timezone = Config::get('services.bursam.timezone');
        $now = now($timezone);
        $marketOpeningStartTime = Config::get('services.bursam.market_opening_start_time');
        $marketOpeningEndTime = Config::get('services.bursam.market_opening_end_time');
        $fridayBreakStartTime = Config::get('services.bursam.friday_break_start_time');
        $fridayBreakEndTime = Config::get('services.bursam.friday_break_end_time');

        $marketOpeningStartDateTime = now($timezone)->setTimeFromTimeString($marketOpeningStartTime);
        $marketOpeningEndDateTime = now($timezone)->setTimeFromTimeString($marketOpeningEndTime);
        $fridayBreakStartDateTime = now($timezone)->setTimeFromTimeString($fridayBreakStartTime);
        $fridayBreakEndDateTime = now($timezone)->setTimeFromTimeString($fridayBreakEndTime);

        if ($now->isAfter($marketOpeningEndDateTime)) {
            $marketOpeningEndDateTime->addDay();
        } else {
            $marketOpeningStartDateTime->subDay();
        }

        if (
            ! $now->between($marketOpeningStartDateTime, $marketOpeningEndDateTime)
            || ($now->isFriday() && $now->between($fridayBreakStartDateTime, $fridayBreakEndDateTime))
        ) {
            return false;
        }

        return true;
    }
}

if (! function_exists('parse_number')) {
    function parse_number($number): float
    {
        return (float) preg_replace('/[^\d.]/', '', $number);
    }
}
