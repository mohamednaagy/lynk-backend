<?php

namespace App\Http\Controllers\Api\V1\Lender\Settings;

use App\Actions\Contracts\GetSettingsClassInstance;
use App\Enums\Area;
use App\Http\Controllers\Controller;
use Illuminate\Support\Arr;

class GetLenderAreaSettings extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function __invoke(GetSettingsClassInstance $getSettingsClassInstance)
    {
        $settings = Arr::only(
            $getSettingsClassInstance->handle(Area::Lender)->toArray(),
            [
                'email_verification_enabled',
            ]
        );

        return $this->successResponse($settings);
    }
}
