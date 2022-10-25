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
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function __invoke(GetSettingsClassInstance $getSettingsClassInstance)
    {
        return $this->successResponse($getSettingsClassInstance->handle(Area::Lender)->toArray());
    }
}
