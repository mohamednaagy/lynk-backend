<?php

namespace App\Http\Controllers\Api\V1\Admin\Settings;

use App\Actions\Contracts\LocalMurabaha\GetLocalMurabahaSettings;
use App\Actions\Contracts\UpdateSettings;
use App\Enums\Action;
use App\Enums\Area;
use App\Enums\Subject;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Admin\Settings\UpdateLocalMurabahaSettingsRequest;
use App\Transformers\LocalMurabahaSettingsTransformer;
use Illuminate\Http\JsonResponse;

class LocalMurabahaSettingsController extends Controller
{
    public function __construct()
    {
        $this->middleware(
            'permission:'.
                perm(Area::SuperAdmin, [Subject::LocalMurabahaAreaSettings, Action::Index, Action::Manage])
        )->only('index');

        $this->middleware(
            'permission:'.
                perm(Area::SuperAdmin, [Subject::LocalMurabahaAreaSettings, Action::Edit, Action::Manage])
        )->only('update');
    }

    /**
     * Display a listing of the resource.
     */
    public function index(GetLocalMurabahaSettings $getLocalMurabahaSettings): JsonResponse
    {
        return fractal($getLocalMurabahaSettings->handle(), new LocalMurabahaSettingsTransformer)->respond();
    }

    /**
     * Handle the incoming request.
     */
    public function update(
        UpdateLocalMurabahaSettingsRequest $updateLocalMurabahaSettingsRequest,
        UpdateSettings $updateSettings
    ): JsonResponse {
        $data = $updateLocalMurabahaSettingsRequest->validated();
        $data['area'] = 'LocalMurabaha';
        $updateSettings->handle($data);

        return $this->successResponse([
            'default_trade_order_rotation_count' => $data['default_trade_order_rotation_count'],
            'default_contract_sign_time_limit' => $data['default_contract_sign_time_limit'],
        ]);
    }
}
