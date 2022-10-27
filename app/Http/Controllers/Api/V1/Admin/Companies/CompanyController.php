<?php

namespace App\Http\Controllers\Api\V1\Admin\Companies;

use App\Actions\Contracts\Companies\GetCompanies;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Admin\Companies\GetCompaniesRequest;
use App\Models\Company;
use App\Transformers\CompanyTransformer;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class CompanyController extends Controller
{
    public function index(
        GetCompaniesRequest $getCompaniesRequest,
        GetCompanies $getCompanies
    ): JsonResponse {
        return fractal($getCompanies->handle(), new CompanyTransformer())->respond();
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return JsonResponse
     */
    public function destroy(Company $company): JsonResponse
    {
        if ($company->where('id', $company->id)->delete()) {
            DB::table('companies')->update(['unique_name' => null]);
        }

        return $this->successResponse();
    }
}
