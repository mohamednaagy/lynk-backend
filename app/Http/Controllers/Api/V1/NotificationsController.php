<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Transformers\NotificationTransformer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use League\Fractal\Pagination\IlluminatePaginatorAdapter;
use Symfony\Component\HttpFoundation\Response;

class NotificationsController extends Controller
{
    public const NOTIFICATIONS_DAYS = 30;

    public const NOTIFICATIONS_PER_PAGE = 10;

    public function index(Request $request)
    {
        $user = Auth::user();

        $query = $user->notifications();

        // Filter for unread notifications only if requested
        if ($request->boolean('unread_only')) {
            $query->whereNull('read_at');
        }

        // Get notifications for the authenticated user from the last 30 days
        $notifications = $query
            ->where('created_at', '>=', now()->subDays(self::NOTIFICATIONS_DAYS))
            ->orderBy('created_at', 'desc')
            ->paginate(self::NOTIFICATIONS_PER_PAGE);

        // Transform the response using Fractal
        return fractal($notifications, new NotificationTransformer)
            ->paginateWith(new IlluminatePaginatorAdapter($notifications))
            ->respond();
    }

    /**
     * Mark a notification as read
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function markAsRead($id)
    {
        /* @var User $user */
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
            'message' => 'Notification marked as read',
        ], Response::HTTP_OK);
    }

    /**
     * Mark all notifications as read
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function markAllAsRead()
    {
        /* @var User $user */
        $user = Auth::user();

        $user->unreadNotifications->each->markAsRead();

        return response()->json([
            'message' => 'All notifications marked as read',
        ]);
    }
}
