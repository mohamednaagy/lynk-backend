<?php

namespace App\Http\Controllers\Api\V1\Admin\Settings;

use App\Actions\Contracts\ListSettings;
use App\Actions\Contracts\UpdateSettings;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Admin\Settings\UpdateSettingsRequest;
use App\Http\Resources\SettingsResource;
use Illuminate\Http\JsonResponse;

class SettingsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param  ListSettings  $listSettings
     * @return SettingsResource
     */
    public function index(ListSettings $listSettings): SettingsResource
    {
        return new SettingsResource($listSettings->handle());
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  UpdateSettingsRequest  $updateSettingsRequest
     * @param  UpdateSettings  $updateSettings
     * @return JsonResponse
     */
    public function update(UpdateSettingsRequest $updateSettingsRequest, UpdateSettings $updateSettings): JsonResponse
    {
        $updateSettings->handle($updateSettingsRequest->validated());

        return $this->successResponse();
    }
}
