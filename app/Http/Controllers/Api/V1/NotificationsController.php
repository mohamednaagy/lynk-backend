<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Contracts\Notifications\BuildUserNotificationsQuery;
use App\Actions\Contracts\Notifications\BuildUserUnreadNotificationsQuery;
use App\Http\Controllers\Controller;
use App\Transformers\NotificationTransformer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use League\Fractal\Pagination\IlluminatePaginatorAdapter;
use Symfony\Component\HttpFoundation\Response;

class NotificationsController extends Controller
{
    /**
     * Get a paginated list of user notifications.
     */
    public function index(Request $request, BuildUserNotificationsQuery $builder): JsonResponse
    {
        $query = $builder
            ->setUser(Auth::user())
            ->handle();

        // Get notifications for the authenticated user from the last 30 days
        $notifications = $query
            ->paginate(config('notifications.panel.count_per_page'));

        // Transform the response using Fractal
        return fractal($notifications, new NotificationTransformer)
            ->paginateWith(new IlluminatePaginatorAdapter($notifications))
            ->respond();
    }

    public function unreadCount(Request $request, BuildUserUnreadNotificationsQuery $query): JsonResponse
    {
        $unreadCount = $query->setUser(Auth::user())->handle();

        return response()->json(['unread_count' => $unreadCount]);
    }

    /**
     * Mark a notification as read.
     *
     * @param  string  $id  The ID of the notification to mark as read.
     */
    public function markAsRead(string $id): JsonResponse
    {
        $user = Auth::user();

        $notification = $user->notifications()->where('id', $id)->firstOrFail();
        $notification->markAsRead();

        return response()->json([
            'message' => __('notification.notification-marked-read'),
        ], Response::HTTP_OK);
    }

    /**
     * Mark a notification as un-read.
     *
     * @param  string  $id  The ID of the notification to mark as un-read.
     */
    public function markAsUnread(string $id): JsonResponse
    {
        $user = Auth::user();

        $notification = $user->notifications()->where('id', $id)->firstOrFail();
        $notification->markAsUnRead();

        return response()->json([
            'message' => __('notification.notification-marked-unread'),
        ], Response::HTTP_OK);
    }

    /**
     * Mark all notifications as read
     */
    public function markAllAsRead(): JsonResponse
    {
        $user = Auth::user();

        $user->unreadNotifications()->update(['read_at' => now()]);

        return response()->json([
            'message' => __('notification.notification-all-marked-read'),
        ], Response::HTTP_OK);
    }
}
