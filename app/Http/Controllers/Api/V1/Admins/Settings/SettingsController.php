<?php

namespace App\Http\Controllers\Api\V1\Settings;

use App\Actions\Contracts\UpdateSettings;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Actions\Contracts\ListSettings;
use App\Http\Resources\SettingsResource;
use App\Http\Requests\Settings\UpdateSettingsRequest;

class SettingsController extends Controller
{
    /**
     * Display a listing of the resource.
     * @param ListSettings $listSettings
     * @return SettingsResource
     */
    public function index(ListSettings $listSettings)
    {
        return new SettingsResource($listSettings->handle());
    }

    /**
     * Update the specified resource in storage.
     * @param UpdateSettingsRequest $updateSettingsRequest
     * @param UpdateSettings $updateSettings
     * @return JsonResponse
     */
    public function update(UpdateSettingsRequest $updateSettingsRequest, UpdateSettings $updateSettings): JsonResponse
    {
        $updateSettings->handle($updateSettingsRequest->validated());

        return $this->successResponse();
    }

}
