<?php

namespace App\Http\Controllers\Api\V1\Admin\Settings;

use App\Actions\Contracts\InternationalMurabaha\GetInternationalMurabahaSettings;
use App\Actions\Contracts\UpdateSettings;
use App\Enums\Action;
use App\Enums\Area;
use App\Enums\Subject;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Admin\Settings\UpdateInternationalMurabahaSettingsRequest;
use App\Transformers\InternationalMurabahaSettingsTransformer;
use Illuminate\Http\JsonResponse;

class InternationalMurabahaSettingsController extends Controller
{
    public function __construct()
    {
        $this->middleware(
            'permission:'.
            perm(Area::SuperAdmin, [Subject::InternationalMurabahaAreaSettings, Action::Index, Action::Manage])
        )->only('index');

        $this->middleware(
            'permission:'.
            perm(Area::SuperAdmin, [Subject::InternationalMurabahaAreaSettings, Action::Edit, Action::Manage])
        )->only('update');
    }

    public function index(GetInternationalMurabahaSettings $getInternationalMurabahaSettings): JsonResponse
    {
        return fractal($getInternationalMurabahaSettings->handle(), new InternationalMurabahaSettingsTransformer)->respond();
    }

    public function update(
        UpdateInternationalMurabahaSettingsRequest $updateInternationalMurabahaSettingsRequest,
        UpdateSettings $updateSettings,
        GetInternationalMurabahaSettings $getInternationalMurabahaSettings
    ): JsonResponse {
        $data = $updateInternationalMurabahaSettingsRequest->validated();
        $data['area'] = 'InternationalMurabaha';
        $updateSettings->handle($data);

        return fractal($getInternationalMurabahaSettings->handle(), new InternationalMurabahaSettingsTransformer)->respond();
    }
}
