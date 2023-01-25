<?php

namespace App\Http\Controllers\Api\V1\Admin\Traders;

use App\Actions\Contracts\Companies\UpdateCompany;
use App\Enums\Action;
use App\Enums\Area;
use App\Enums\CompanyType;
use App\Enums\Subject;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Admin\Companies\UpdateCompanyStatusRequest;
use App\Models\Company;
use Illuminate\Http\JsonResponse;

class UpdateTraderStatus extends Controller
{
    public function __construct()
    {
        $this->middleware(
            'permission:'.
                perm(Area::SuperAdmin, [Subject::TraderStatus, Action::Edit, Action::Manage])
        );
    }

    /**
     * Summary of __invoke
     *
     * @param  UpdateCompanyStatusRequest  $request
     * @param  Company  $company
     * @param  UpdateCompany  $updateCompany
     * @return JsonResponse
     */
    public function __invoke(
        UpdateCompanyStatusRequest $request,
        Company $company,
        UpdateCompany $updateCompany
    ): JsonResponse {
        abort_if($company->type !== CompanyType::Trader, 404);

        $updateCompany->handle($company, $request->validated());

        if ($company->wasChanged('status')) {
            activity()
                ->withProperties([
                    'company_id' => $company->id,
                    'old_status' => $company->getOriginal('status'),
                    'new_Status' => $company->getAttribute('status'),
                ])
                ->log('Company status changed');
        }

        return $this->successResponse();
    }
}
