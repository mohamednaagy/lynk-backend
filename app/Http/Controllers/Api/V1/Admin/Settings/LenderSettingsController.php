<?php

namespace App\Http\Controllers\Api\V1\Admin\Settings;

use App\Actions\Contracts\GetSettingsClassInstance;
use App\Actions\Contracts\UpdateSettings;
use App\Enums\Action;
use App\Enums\Area;
use App\Enums\Subject;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Admin\Settings\UpdateLenderSettingsRequest;
use App\Transformers\LenderSettingsTransformer;
use Illuminate\Http\JsonResponse;

class LenderSettingsController extends Controller
{
    public function __construct()
    {
        $this->middleware(
            'permission:'.
                perm(Area::SuperAdmin, [Subject::LenderAreaSettings, Action::Index, Action::Manage])
        )->only('index');

        $this->middleware(
            'permission:'.
                perm(Area::SuperAdmin, [Subject::LenderAreaSettings, Action::Edit, Action::Manage])
        )->only('update');
    }

    /**
     * Display a listing of the resource.
     *
     * @param  GetSettingsClassInstance  $getSettingsClassInstance
     * @return JsonResponse
     */
    public function index(GetSettingsClassInstance $getSettingsClassInstance): JsonResponse
    {
        return fractal($getSettingsClassInstance->handle(Area::Lender), new LenderSettingsTransformer())
            ->parseIncludes([
                'default_order_cost',
                'email_verification_enabled',
                'default_does_order_require_approval',
                'default_company_registration_status',
                'default_company_status_created_by_operation',
                'notify_about_new_orders',
            ])
            ->respond();
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  UpdateLenderSettingsRequest  $updateLenderSettingsRequest
     * @param  UpdateSettings  $updateSettings
     * @return JsonResponse
     */
    public function update(UpdateLenderSettingsRequest $updateLenderSettingsRequest, UpdateSettings $updateSettings): JsonResponse
    {
        $data = $updateLenderSettingsRequest->validated();
        $data['area'] = Area::Lender;
        $updateSettings->handle($data);

        return $this->successResponse();
    }
}
