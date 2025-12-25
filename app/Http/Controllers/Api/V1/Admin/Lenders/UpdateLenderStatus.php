<?php

namespace App\Http\Controllers\Api\V1\Admin\Lenders;

use App\Actions\Contracts\Companies\UpdateCompany;
use App\Enums\Action;
use App\Enums\Area;
use App\Enums\Subject;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Admin\Companies\UpdateCompanyStatusRequest;
use App\Models\Lender;
use Illuminate\Http\JsonResponse;

class UpdateLenderStatus extends Controller
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
     */
    public function __invoke(
        UpdateCompanyStatusRequest $request,
        Lender $lender,
        UpdateCompany $updateCompany
    ): JsonResponse {
        $updateCompany->handle($lender, $request->validated());

        return $this->successResponse([]);
    }
}
