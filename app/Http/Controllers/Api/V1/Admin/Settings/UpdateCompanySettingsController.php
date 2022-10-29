<?php

namespace App\Http\Controllers\Api\V1\Admin\Settings;

use App\Actions\Contracts\Companies\UpdateCompany;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Admin\Settings\UpdateCompanySettingsRequest;
use App\Models\Company;
use Illuminate\Http\JsonResponse;

class UpdateCompanySettingsController extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  UpdateCompanySettingsRequest  $request
     * @param  Company  $company
     * @param  UpdateCompany  $updateCompany
     * @return JsonResponse
     */
    public function __invoke(
        UpdateCompanySettingsRequest $request,
        Company $company,
        UpdateCompany $updateCompany
    ): JsonResponse {
        $updateCompany->handle($company, $request->validated());

        return $this->successResponse();
    }
}
