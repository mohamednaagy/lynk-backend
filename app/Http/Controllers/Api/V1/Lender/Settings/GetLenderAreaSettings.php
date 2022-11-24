<?php

namespace App\Http\Controllers\Api\V1\Lender\Settings;

use App\Actions\Contracts\GetSettingsClassInstance;
use App\Enums\Area;
use App\Http\Controllers\Controller;
use App\Transformers\LenderSettingsTransformer;
use Illuminate\Http\JsonResponse;

class GetLenderAreaSettings extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  GetSettingsClassInstance  $getSettingsClassInstance
     * @return JsonResponse
     */
    public function __invoke(GetSettingsClassInstance $getSettingsClassInstance)
    {
        return fractal($getSettingsClassInstance->handle(Area::Lender), new LenderSettingsTransformer())
            ->parseIncludes([
                'default_order_cost',
                'default_does_order_require_approval',
            ])->respond();
    }
}
