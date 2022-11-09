<?php

namespace App\Http\Controllers\Api\V1\Admin\Settings;

use App\Actions\Contracts\Wakala\GetWakalaTemplate;
use App\Actions\Contracts\Wakala\UpdateWakalaTemplate;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Admin\Settings\UpdateWakalaTemplateRequest;
use Illuminate\Http\JsonResponse;

class WakalaTemplateController extends Controller
{
    public function show(GetWakalaTemplate $getWakalaTemplate, string $type)
    {
        return $this->successResponse($getWakalaTemplate->handle($type));
    }

    /**
     * Handle the incoming request.
     *
     * @param  UpdateWakalaTemplateRequest  $updateWakalaTemplateRequest
     * @param  UpdateWakalaTemplate  $updateWakalaTemplate
     * @return JsonResponse
     */
    public function update(
        UpdateWakalaTemplateRequest $updateWakalaTemplateRequest,
        UpdateWakalaTemplate $updateWakalaTemplate,
        string $type
    ): JsonResponse {
        $data = $updateWakalaTemplateRequest->validated();
        $data['template_type'] = $type;

        $updateWakalaTemplate->handle($data);

        return $this->successResponse();
    }
}
