<?php

namespace App\Http\Controllers\Api\V1\Lender\Settings;

use App\Actions\Contracts\GetSettingsClassInstance;
use App\Enums\Area;
use App\Http\Controllers\Controller;

class GetLenderAreaSettings extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  GetSettingsClassInstance  $getSettingsClassInstance
     * @return \Illuminate\Http\JsonResponse
     */
    public function __invoke(GetSettingsClassInstance $getSettingsClassInstance)
    {
        // __REVIEW__ create transformer for settings and return only: email_verification_enabled by includes
        return $this->successResponse($getSettingsClassInstance->handle(Area::Lender)->toArray());
    }
}
