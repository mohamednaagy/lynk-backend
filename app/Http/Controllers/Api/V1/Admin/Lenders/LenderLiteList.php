<?php

namespace App\Http\Controllers\Api\V1\Admin\Lenders;

use App\Actions\Contracts\Companies\BuildPaginatedCompaniesQuery;
use App\Enums\Action;
use App\Enums\Area;
use App\Enums\CompanyType;
use App\Enums\Subject;
use App\Http\Controllers\Controller;
use App\Transformers\CompanyTransformer;
use Illuminate\Http\JsonResponse;

class LenderLiteList extends Controller
{
    public function __construct()
    {
        $this->middleware(
            'permission:'.
            perm(Area::SuperAdmin, [Subject::Lenders, Action::Index, Action::Manage])
            .'|'.
            perm(Area::SuperAdmin, [Subject::FinancingOrders, Action::Manage, Action::Index])
        );
    }

    public function __invoke(
        BuildPaginatedCompaniesQuery $buildPaginatedCompaniesQuery
    ): JsonResponse {
        $companies = $buildPaginatedCompaniesQuery->setType(CompanyType::Lender)
            ->handle()
            ->get();

        return fractal($companies, new CompanyTransformer())
            ->parseIncludes([
                'id',
                'name',
                'contract_number',
            ])
            ->respond();
    }
}
