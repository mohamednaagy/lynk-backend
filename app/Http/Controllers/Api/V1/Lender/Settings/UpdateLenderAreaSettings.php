<?php

namespace App\Http\Controllers\Api\V1\Lender\Settings;

use App\Actions\Contracts\UpdateSettings;
use App\Enums\Area;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Lender\Settings\UpdateSettingsRequest;

class UpdateLenderAreaSettings extends Controller
{
    public function __invoke(UpdateSettingsRequest $updateSettingsRequest, UpdateSettings $updateSettings)
    {
        $data = $updateSettingsRequest->validated();
        $data['area'] = Area::Lender;

        $updateSettings->handle($data);

        return $this->successResponse();
    }
}
