<?php

namespace App\Http\Controllers\Api\V1\Admin\Companies;

use App\Actions\Contracts\Companies\UpdateCompany;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Admin\Companies\UpdateCompanyStatusRequest;
use App\Models\Company;
use Illuminate\Http\JsonResponse;

class UpdateCompanyStatus extends Controller
{
    /**
     * Summary of __invoke
     *
     * @param  UpdateCompanyStatusRequest  $updateCompanyStatusRequest
     * @param  Company  $company
     * @param  UpdateCompany  $updateCompany
     * @return JsonResponse
     */
    public function __invoke(
        UpdateCompanyStatusRequest $updateCompanyStatusRequest,
        Company $company,
        UpdateCompany $updateCompany
    ): JsonResponse {
        $updateCompany->handle($company, $updateCompanyStatusRequest->validated());

        return $this->successResponse([]);
    }
}
