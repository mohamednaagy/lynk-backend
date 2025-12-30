<?php

namespace App\Http\Controllers\Api\V1\Lender\Wallets;

use App\Actions\Contracts\Wallets\Notifications\SyncWalletNotification;
use App\Enums\Action;
use App\Enums\Area;
use App\Enums\Subject;
use App\Enums\WalletNotificationType;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Lender\Wallets\WalletNotificationRequest;
use App\Models\Lender;
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
    }

    public function index()
    {
        /** @var Lender $lender */
        $lender = tenant();

        return fractal($lender->walletNotification, new WalletNotificationTransformer)
            ->addMeta([
                'types' => collect(WalletNotificationType::asSelectArray())->reject(function ($item, $value) use ($lender) {
                    return $lender->isTiered() && $value == WalletNotificationType::ORDER_COUNT;
                }),
            ])
            ->respond();
    }

    public function store(WalletNotificationRequest $request, SyncWalletNotification $syncWalletNotification)
    {
        $lender = tenant();

        $notifications = $syncWalletNotification->handle($lender, $request->validated());

        return fractal($notifications, new WalletNotificationTransformer)->respond();
    }
}
