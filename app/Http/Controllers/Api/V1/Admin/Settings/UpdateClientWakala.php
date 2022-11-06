<?php

namespace App\Http\Controllers\Api\V1\Admin\Settings;

use App\Actions\Contracts\Wakala\UpdateWakalaTemplate;
use App\Enums\Area;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Admin\Settings\UpdateWakalaTemplateRequest;
use Illuminate\Http\JsonResponse;

class UpdateClientWakala extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  UpdateWakalaTemplateRequest  $updateWakalaTemplateRequest
     * @param  UpdateWakalaTemplate  $updateWakalaTemplate
     * @return JsonResponse
     */
    public function __invoke(
        UpdateWakalaTemplateRequest $updateWakalaTemplateRequest,
        UpdateWakalaTemplate $updateWakalaTemplate
    ): JsonResponse {
        $data = $updateWakalaTemplateRequest->validated();
        $data['area'] = Area::SuperAdmin;
        $data['templateType'] = 'client';

        $updateWakalaTemplate->handle($data);

        return $this->successResponse();
    }
}
