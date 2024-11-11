<?php

namespace App\Http\Controllers\Api\V1\Lender\Settings;

use App\Enums\Action;
use App\Enums\Area;
use App\Enums\Subject;
use App\Enums\TraderOrderMode;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Lender\Settings\UpdateSettingsRequest;
use App\Transformers\CompanyTransformer;
use Illuminate\Http\JsonResponse;

class SettingsController extends Controller
{
    public function __construct()
    {
        $this->middleware(
            'permission:'.
            perm(Area::Lender, [Subject::LenderSettings, Action::Index, Action::Manage])
        )->only('index');

        $this->middleware(
            'permission:'.
            perm(Area::Lender, [Subject::LenderSettings, Action::Edit, Action::Manage])
        )->only('update');
    }

    public function index()
    {
        $company = tenant();

        return fractal($company, new CompanyTransformer)
            ->parseIncludes([
                'order_cost',
                'does_order_require_approval',
                'webhook_secret_key',
                'require_initiate_trade_request',
                'auto_complete_murabaha_order',
                'notify_borrowers_about_order_updates',
                'force_unique_reference_number',
            ])->respond();
    }

    public function update(UpdateSettingsRequest $updateSettingsRequest): JsonResponse
    {
        $company = tenant();

        $data = $updateSettingsRequest->validated();
        if ($company->trading_mode->is(TraderOrderMode::Manual)) {
            $data['require_initiate_trade_request'] = true;
        }

        $company->update($data);

        return $this->successResponse([]);
    }
}
