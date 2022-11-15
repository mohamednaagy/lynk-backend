<?php

namespace App\Http\Controllers\Api\V1\Admin\Settings;

use App\Actions\Contracts\ProjectSettings\GetProjectSettings;
use App\Actions\Contracts\ProjectSettings\UpdateProjectSettings;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Admin\Settings\UpdateProjectSettingsRequest;
use App\Transformers\ProjectSettingsTransformer;
use Illuminate\Http\JsonResponse;

class ProjectSettingsController extends Controller
{
    public function show(GetProjectSettings $getProjectSettings): JsonResponse
    {
        return fractal($getProjectSettings->handle(), new ProjectSettingsTransformer())->respond();
    }

    /**
     * Handle the incoming request.
     *
     * @param  UpdateProjectSettingsRequest  $updateProjectSettingsRequest
     * @param  UpdateProjectSettings  $updateProjectSettings
     * @return JsonResponse
     */
    public function update(
        UpdateProjectSettingsRequest $updateProjectSettingsRequest,
        UpdateProjectSettings $updateProjectSettings
    ): JsonResponse {
        $data = $updateProjectSettingsRequest->validated();
        $data['vat_rate'] /= 100;

        return fractal($updateProjectSettings->handle($data), new ProjectSettingsTransformer())->respond();
    }
}
