<?php

namespace App\Http\Controllers\Api\V1\Admin\Settings;

use App\Actions\Contracts\Wakala\GetWakalaTemplate;
use App\Actions\Contracts\Wakala\UpdateWakalaTemplate;
use App\Enums\Action;
use App\Enums\Area;
use App\Enums\Subject;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Admin\Settings\UpdateWakalaTemplateRequest;
use Illuminate\Http\JsonResponse;

class WakalaTemplateController extends Controller
{
    public function __construct()
    {
        $this->middleware(
            'permission:'.
            perm(Area::SuperAdmin, [Subject::WakalaTemplates, Action::Index, Action::Manage])
        )->only('index');

        $this->middleware(
            'permission:'.
            perm(Area::SuperAdmin, [Subject::WakalaTemplates, Action::Edit, Action::Manage])
        )->only('update');
    }

    /**
     * @param  GetWakalaTemplate  $getWakalaTemplate
     * @param  string  $type
     * @return JsonResponse
     */
    public function index(GetWakalaTemplate $getWakalaTemplate, string $type): JsonResponse
    {
        return $this->successResponse($getWakalaTemplate->handle($type));
    }

    /**
     * Handle the incoming request.
     *
     * @param  UpdateWakalaTemplateRequest  $updateWakalaTemplateRequest
     * @param  UpdateWakalaTemplate  $updateWakalaTemplate
     * @param  string  $type
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
