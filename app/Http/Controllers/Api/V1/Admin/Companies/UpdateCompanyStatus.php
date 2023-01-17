<?php

namespace App\Http\Controllers\Api\V1\Admin\Companies;

use App\Actions\Contracts\Companies\UpdateCompany;
use App\Enums\Action;
use App\Enums\Area;
use App\Enums\Subject;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Admin\Companies\UpdateCompanyStatusRequest;
use App\Models\Company;
use Illuminate\Http\JsonResponse;

class UpdateCompanyStatus extends Controller
{
    public function __construct()
    {
        $this->middleware(
            'permission:'.
            perm(Area::SuperAdmin, [Subject::Lenders, Action::Edit, Action::Manage])
        );
    }

    /**
     * Summary of __invoke
     *
     * @param  UpdateCompanyStatusRequest  $updateCompanyStatusRequest
     * @param  Company  $company
     * @param  UpdateCompany  $updateCompany
     * @return JsonResponse
     */
    public function __invoke(
        UpdateCompanyStatusRequest $request,
        Company $company,
        UpdateCompany $updateCompany
    ): JsonResponse {
        $updateCompany->handle($company, $request->validated());

        return $this->successResponse([]);
    }
}
