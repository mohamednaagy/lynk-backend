<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Transformers\NotificationTransformer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use League\Fractal\Pagination\IlluminatePaginatorAdapter;

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
}
