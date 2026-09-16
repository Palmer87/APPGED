<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\NotificationResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;

class NotificationController extends Controller
{
    /**
     * List paginated notifications for the authenticated user.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $perPage = max(1, min((int) $request->input('per_page', 25), 100));

        $query = $request->boolean('unread')
            ? $user->unreadNotifications()
            : $user->notifications();

        $notifications = $query->latest('created_at')->paginate($perPage);

        return response()->json([
            'data' => NotificationResource::collection($notifications),
            'meta' => [
                'current_page' => $notifications->currentPage(),
                'per_page' => $notifications->perPage(),
                'total' => $notifications->total(),
                'last_page' => $notifications->lastPage(),
                'unread_count' => $user->unreadNotifications()->count(),
            ],
        ]);
    }

    /**
     * Get unread notifications only.
     */
    public function unread(Request $request): JsonResponse
    {
        $user = $request->user();
        $perPage = max(1, min((int) $request->input('per_page', 25), 100));

        $unreadNotifications = $user->unreadNotifications()
            ->latest('created_at')
            ->paginate($perPage);

        return response()->json([
            'data' => NotificationResource::collection($unreadNotifications),
            'meta' => [
                'current_page' => $unreadNotifications->currentPage(),
                'per_page' => $unreadNotifications->perPage(),
                'total' => $unreadNotifications->total(),
                'last_page' => $unreadNotifications->lastPage(),
                'unread_count' => $user->unreadNotifications()->count(),
            ],
        ]);
    }

    /**
     * Mark a notification as read.
     */
    public function markAsRead(Request $request, string $id): JsonResponse
    {
        $notification = DatabaseNotification::find($id);

        if (! $notification) {
            abort(404, 'Notification not found.');
        }

        if ($notification->notifiable_type !== User::class || (int) $notification->notifiable_id !== (int) $request->user()->id) {
            abort(403, 'Unauthorized access to notification.');
        }

        if ($notification->read_at === null) {
            $notification->markAsRead();
        }

        return response()->json([
            'message' => 'Notification marked as read.',
            'unread_count' => $request->user()->unreadNotifications()->count(),
        ]);
    }

    /**
     * Mark all notifications as read.
     */
    public function markAllAsRead(Request $request): JsonResponse
    {
        $user = $request->user();
        $user->unreadNotifications->markAsRead();

        return response()->json([
            'message' => 'All notifications marked as read.',
            'unread_count' => 0,
        ]);
    }

    /**
     * Delete a notification.
     */
    public function destroy(Request $request, string $id): JsonResponse
    {
        $notification = DatabaseNotification::find($id);

        if (! $notification) {
            abort(404, 'Notification not found.');
        }

        if ($notification->notifiable_type !== User::class || (int) $notification->notifiable_id !== (int) $request->user()->id) {
            abort(403, 'Unauthorized access to notification.');
        }

        $notification->delete();

        return response()->json([
            'message' => 'Notification deleted successfully.',
            'unread_count' => $request->user()->unreadNotifications()->count(),
        ]);
    }
}
