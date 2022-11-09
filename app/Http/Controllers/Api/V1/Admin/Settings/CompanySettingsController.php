<?php

namespace App\Http\Controllers\Api\V1\Admin\Settings;

use App\Actions\Contracts\CompanySettings\GetCompanySettings;
use App\Actions\Contracts\CompanySettings\UpdateCompanySettings;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Admin\Settings\UpdateCompanySettingsRequest;
use App\Transformers\CompanyInfoTransformer;
use Illuminate\Http\JsonResponse;

class CompanySettingsController extends Controller
{
    public function show(GetCompanySettings $getCompanySettings): JsonResponse
    {
        return fractal($getCompanySettings->handle(), new CompanyInfoTransformer())->respond();
    }

    /**
     * Handle the incoming request.
     *
     * @param  UpdateCompanySettingsRequest  $updateCompanySettingsRequest
     * @param  UpdateCompanySettings  $updateCompanySettings
     * @return JsonResponse
     */
    public function update(
        UpdateCompanySettingsRequest $updateCompanySettingsRequest,
        UpdateCompanySettings $updateCompanySettings
    ): JsonResponse {
        $data = $updateCompanySettingsRequest->validated();

        return fractal($updateCompanySettings->handle($data), new CompanyInfoTransformer())->respond();
    }
}
