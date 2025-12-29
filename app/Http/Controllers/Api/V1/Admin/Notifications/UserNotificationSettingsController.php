<?php

namespace App\Http\Controllers\Api\V1\Admin\Notifications;

use App\Enums\Action;
use App\Enums\Area;
use App\Enums\NotificationChannel;
use App\Enums\Subject;
use App\Enums\SystemNotificationType;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\NotificationPreferenceService;
use App\Transformers\NotificationSettingTransformer;
use Illuminate\Http\JsonResponse;

class UserNotificationSettingsController extends Controller
{
    public function __construct()
    {
        $this->middleware(
            'permission:'.
            perm(Area::SuperAdmin, [Subject::Admins, Action::Show, Action::Manage])
        )->only('index');

        $this->middleware(
            'permission:'.
            perm(Area::SuperAdmin, [Subject::Admins, Action::Edit, Action::Manage])
        )->only('toggle');
    }

    public function index(User $user, NotificationPreferenceService $service): JsonResponse
    {
        return fractal($service->listForUser($user), new NotificationSettingTransformer)->respond();
    }

    public function toggleChannel(
        User $user,
        SystemNotificationType $notificationType,
        NotificationChannel $channel,
        NotificationPreferenceService $service
    ): JsonResponse {
        $isEnabled = $service->isChannelEnabled($user, $notificationType, $channel);
        $service->setChannelNotification($user, $notificationType, $channel, ! $isEnabled);

        return fractal($service->listForUser($user), new NotificationSettingTransformer)->respond();
    }
}
