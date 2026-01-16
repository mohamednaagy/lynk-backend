<?php

namespace App\Http\Controllers\Api\V1;

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
    public function index(Request $request): JsonResponse
    {
        $query = Auth::user()->notifications();

        // Filter for unread notifications only if requested
        $query->when($request->boolean('unread_only'), function ($query) {
            $query->whereNull('read_at');
        });

        // Get notifications for the authenticated user from the last 30 days
        $notifications = $query
            ->where('created_at', '>=', now()->subDays(config('notifications.panel.days')))
            ->latest()
            ->paginate(config('notifications.panel.count_per_page'));

        // Transform the response using Fractal
        return fractal($notifications, new NotificationTransformer)
            ->paginateWith(new IlluminatePaginatorAdapter($notifications))
            ->respond();
    }

    /**
     * Mark a notification as read.
     *
     * @param  int  $id  The ID of the notification to mark as read.
     */
    public function markAsRead(int $id): JsonResponse
    {
        $user = Auth::user();

        $notification = $user->notifications()->where('id', $id)->first();

        if (! $notification) {
            return response()->errorResponse(
                trans('error.item_not_found'),
                Response::HTTP_NOT_FOUND,
                \App\Enums\ErrorCode::ITEM_NOT_FOUND
            );
        }

        $notification->markAsRead();

        return response()->json([
            'message' => __('notification.notification-marked-read'),
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
