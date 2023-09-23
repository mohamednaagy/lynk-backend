<?php

namespace App\Http\Controllers\Api\V1\Lender\Wallets;

use App\Actions\Contracts\Wallets\Notifications\SyncWalletNotification;
use App\Enums\Action;
use App\Enums\Area;
use App\Enums\Subject;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Lender\Wallets\WalletNotificationRequest;
use App\Models\WalletNotification;
use App\Transformers\WalletNotificationTransformer;

class WalletNotificationController extends Controller
{
    public function __construct()
    {
        $this->middleware(
            'permission:'.
            perm(Area::Lender, [Subject::WalletNotifications, Action::Index, Action::Manage])
        )->only('index');

        $this->middleware(
            'permission:'.
            perm(Area::Lender, [Subject::WalletNotifications, Action::Create, Action::Manage])
        )->only('store');

        $this->middleware(
            'permission:'.
            perm(Area::Lender, [Subject::WalletNotifications, Action::Delete, Action::Manage])
        )->only('destroy');
    }

    public function index()
    {
        $company = tenant();

        return fractal($company->walletNotification, new WalletNotificationTransformer)->respond();
    }

    public function store(WalletNotificationRequest $request, SyncWalletNotification $syncWalletNotification)
    {
        $company = tenant();

        $notifications = $syncWalletNotification->handle($company, $request->validated());

        return fractal($notifications, new WalletNotificationTransformer)->respond();
    }

    public function destroy(WalletNotification $walletNotification)
    {
        $walletNotification->delete();

        return $this->successResponse();
    }
}
