<?php

namespace App\Http\Controllers\Api\V1\Admin\Settings;

use App\Actions\Contracts\ListSettings;
use App\Actions\Contracts\UpdateSettings;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Admin\Settings\UpdateSettingsRequest;
use App\Http\Resources\SettingsResource;
use Illuminate\Http\JsonResponse;

// __REVIEW__ change filename and class to LenderSettingsController
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
        // __REVIEW__ Return the following values of LenderAreaSettings:
        // default_company_registration_status
        // default_company_status_created_by_operation
        // default_does_order_require_approval
        // email_verification_enabled
        // default_order_cost

        // __REVIEW__ use transformer or direct $this->successResponse();
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
        // __REVIEW__ Update the following values of LenderAreaSettings:
        // default_company_registration_status
        // default_company_status_created_by_operation
        // default_does_order_require_approval
        // email_verification_enabled
        // default_order_cost
        $updateSettings->handle($updateSettingsRequest->validated());

        return $this->successResponse();
    }
}
