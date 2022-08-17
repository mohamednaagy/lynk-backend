<?php

namespace Modules\Admin\Http\Controllers\Api\V1\Settings;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Admin\Http\Requests\Settings\UpdateSettingsRequest;
use Modules\Admin\Http\Resources\SettingsResource;
use Modules\Admin\Services\SettingService;

class SettingsController extends Controller
{
    protected SettingService $settingService;

    public function __construct(SettingService $settingService)
    {
        $this->settingService = $settingService;
    }

    /**
     * Display a listing of the resource.
     * @return SettingsResource
     */
    public function index()
    {
        return new SettingsResource($this->settingService->listSettings());
    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(UpdateSettingsRequest $updateSettingsRequest)
    {
        $this->settingService->update($updateSettingsRequest->validated());

        return response()->json([]);
    }

}
