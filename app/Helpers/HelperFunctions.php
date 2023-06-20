<?php

use App\Enums\BursamMurabhaStep;
use App\Enums\DmccMurabhaStep;
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

if (! function_exists('get_murabha_step_enum')) {
    function get_murabha_step_enum(?string $provider = null): string
    {
        $provider = $provider ?? config('trader.default');

        return match ($provider) {
            'dmcc', 'fake' => DmccMurabhaStep::class,
            'bursam' => BursamMurabhaStep::class,
            default => throw new \InvalidArgumentException('Invalid trader')
        };
    }
}

if (! function_exists('get_latest_version_of_trader')) {
    function get_latest_version_of_trader($provider): string
    {
        return config('trader.providers.'.$provider.'.latest');
    }
}

if (! function_exists('get_murabha_steps')) {
    function get_murabha_steps($provider, ?string $version = null): array
    {
        return config('murabha-steps.'.$provider.'-versions.'.$version ?? get_latest_version_of_trader($provider));
    }
}

if (! function_exists('trader_step_histories')) {
    function trader_step_histories(string $provider, string $version): array
    {
        return match ($provider) {
            'dmcc', 'fake' => DmccMurabhaStep::getStepsOfVersion($version),
            'bursam' => BursamMurabhaStep::getStepsOfVersion($version),
            default => throw new \InvalidArgumentException('Invalid trader')
        };
    }
}
