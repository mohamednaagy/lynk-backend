<?php

namespace App\Http\Controllers\Api\V1\Admin\Notifications;

use App\Enums\Action;
use App\Enums\Area;
use App\Enums\Subject;
use App\Enums\SystemNotificationType;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\NotificationPreferenceService;
use App\Transformers\NotificationSettingTransformer;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

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

    public function toggleEmail(
        User $user,
        SystemNotificationType $notificationType,
        NotificationPreferenceService $service
    ): JsonResponse {
        $isEnabled = $service->isEmailEnabled($user, $notificationType);
        $service->setEmailNotification($user, $notificationType, ! $isEnabled);

        return fractal($service->listForUser($user), new NotificationSettingTransformer)->respond();
    }

    public function togglePortal(
        User $user,
        SystemNotificationType $notificationType,
        NotificationPreferenceService $service
    ): JsonResponse {
        // Portal notifications are NOT editable per requirements
        // We return an error response to indicate that portal notifications cannot be toggled
        return response()->json([
            'message' => 'Portal notifications cannot be modified as per system requirements',
            'errors' => [
                'portal_notifications' => ['Portal notifications are not editable'],
            ],
        ], Response::HTTP_UNPROCESSABLE_ENTITY);
    }
}
