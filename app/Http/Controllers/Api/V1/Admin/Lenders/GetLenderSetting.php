<?php

namespace App\Http\Controllers\Api\V1\Admin\Lenders;

use App\Http\Controllers\Controller;
use App\Models\Lender;
use App\Transformers\CompanySettingTransformer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GetLenderSetting extends Controller
{
    /**
     * Handle an authentication attempt.
     */
    public function __invoke(Request $request, Lender $lender): JsonResponse
    {
        return fractal($lender, new CompanySettingTransformer)
            ->respond();
    }
}
