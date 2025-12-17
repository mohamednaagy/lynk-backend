<?php

namespace App\Http\Controllers\Api\V1\Lender\Settings;

use App\Actions\Lenders\UpdateLenderSettingsAction;
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
        $lender = tenant();

        return fractal($lender, new CompanyTransformer)
            ->parseIncludes([
                'order_cost',
                'does_order_require_approval',
                'webhook_secret_key',
                'require_initiate_trade_request',
                'force_unique_reference_number',
                'token_expire_in',
            ])->respond();
    }

    public function update(UpdateSettingsRequest $request, UpdateLenderSettingsAction $settings): JsonResponse
    {
        $lender = tenant();
        $data = $request->validated();

        // Set require_initiate_trade_request to true for manual mode
        if ($lender->lenderDetail->trading_mode->is(TraderOrderMode::Manual)) {
            $data['require_initiate_trade_request'] = true;
        }

        $settings->handle($lender, $data);

        return $this->successResponse([]);
    }
}
