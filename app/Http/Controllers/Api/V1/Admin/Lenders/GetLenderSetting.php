<?php

namespace App\Http\Controllers\Api\V1\Admin\Lenders;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Transformers\CompanySettingTransformer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GetLenderSetting extends Controller
{
    /**
     * Handle an authentication attempt.
     *
     * @param  Request  $request
     * @param  Company  $lender
     * @return JsonResponse
     */
    public function __invoke(Request $request, Company $lender): JsonResponse
    {
        return fractal($lender, new CompanySettingTransformer)
            ->respond();
    }
}
