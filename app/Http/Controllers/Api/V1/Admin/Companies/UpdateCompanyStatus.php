<?php

namespace App\Http\Controllers\Api\V1\Admin\Companies;

use App\Actions\Contracts\Companies\UpdateCompanyStatus as UpdateCompanyStatusInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Admin\Companies\UpdateCompanyStatusRequest;
use App\Models\Company;
use Illuminate\Http\JsonResponse;

class UpdateCompanyStatus extends Controller
{
    /**
     * @param  Company  $company
     * @param  UpdateCompanyStatusRequest  $updateCompanyStatusRequest
     * @param  UpdateCompanyStatusInterface  $updateCompanyStatus
     * @return JsonResponse
     */
    public function __invoke(
        Company $company,
        UpdateCompanyStatusRequest $updateCompanyStatusRequest,
        UpdateCompanyStatusInterface $updateCompanyStatus
    ) {
        $updateCompanyStatus->handle($company, $updateCompanyStatusRequest->validated());

        return $this->successResponse([]);
    }
}
