<?php

namespace App\Http\Controllers\Api\V1\Admin\Companies;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Transformers\CompanySettingTransformer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GetCompanySetting extends Controller
{
    /**
     * Handle an authentication attempt.
     *
     * @param  Request  $request
     * @param  Company  $company
     * @return JsonResponse
     */
    public function __invoke(Request $request, Company $company): JsonResponse
    {
        return fractal($company, new CompanySettingTransformer)
            ->respond();
    }
}
