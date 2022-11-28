<?php

namespace App\Http\Controllers\Api\V1\Lender\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Lender\Settings\UpdateSettingsRequest;
use App\Transformers\CompanyTransformer;

class SettingsController extends Controller
{
    public function index()
    {
        $company = tenant();

        return fractal($company, new CompanyTransformer())
            ->parseIncludes([
                'order_cost',
                'does_order_require_approval',
                'webhook_secret_key',
            ])->respond();
    }

    public function update(UpdateSettingsRequest $updateSettingsRequest)
    {
        $company = tenant();

        $data = $updateSettingsRequest->validated();
        $company->update($data);

        return $this->successResponse([]);
    }
}
