<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\NotificationPreferenceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Inertia\Inertia;

class NotificationController extends Controller
{
    public function __construct(
        protected NotificationPreferenceService $preferenceService
    ) {}

    /**
     * List paginated notifications for the authenticated user.
     */
    public function index(Request $request): mixed
    {
        $user = $request->user();
        $perPage = max(1, min((int) $request->input('per_page', 25), 100));

        $query = $request->boolean('unread')
            ? $user->unreadNotifications()
            : $user->notifications();

        $notifications = $query->latest('created_at')->paginate($perPage);

        if ($request->wantsJson() && ! $request->header('X-Inertia')) {
            return response()->json([
                'notifications' => $notifications,
                'unread_count' => $user->unreadNotifications()->count(),
            ]);
        }

        return Inertia::render('Notifications/Index', [
            'notifications' => $notifications,
            'unread_count' => $user->unreadNotifications()->count(),
        ]);
    }

    /**
     * Get unread notifications and count for the authenticated user.
     */
    public function unread(Request $request): JsonResponse
    {
        $user = $request->user();
        $perPage = max(1, min((int) $request->input('per_page', 25), 100));

        $unreadNotifications = $user->unreadNotifications()
            ->latest('created_at')
            ->paginate($perPage);

        return response()->json([
            'unread_notifications' => $unreadNotifications,
            'unread_count' => $user->unreadNotifications()->count(),
        ]);
    }

    /**
     * Mark a specific notification as read.
     */
    public function markAsRead(Request $request, string $id): mixed
    {
        $notification = DatabaseNotification::find($id);

        if (! $notification) {
            abort(404, 'Notification not found');
        }

        if ($notification->notifiable_type !== User::class || (int) $notification->notifiable_id !== (int) $request->user()->id) {
            abort(403, 'Unauthorized access to notification');
        }

        // Idempotent: marking an already read notification as read is a safe no-op
        if ($notification->read_at === null) {
            $notification->markAsRead();
        }

        if ($request->header('X-Inertia')) {
            return back()->with('success', 'Notification marquée comme lue.');
        }

        return response()->json([
            'message' => 'Notification marked as read',
            'notification' => $notification->fresh(),
        ]);
    }

    /**
     * Mark all unread notifications of the authenticated user as read.
     */
    public function markAllAsRead(Request $request): mixed
    {
        $request->user()->unreadNotifications()->update(['read_at' => now()]);

        if ($request->header('X-Inertia')) {
            return back()->with('success', 'Toutes les notifications ont été marquées comme lues.');
        }

        return response()->json([
            'message' => 'All notifications marked as read',
            'unread_count' => 0,
        ]);
    }

    /**
     * Delete a specific notification belonging to the authenticated user.
     */
    public function destroy(Request $request, string $id): mixed
    {
        $notification = DatabaseNotification::find($id);

        if (! $notification) {
            abort(404, 'Notification not found');
        }

        if ($notification->notifiable_type !== User::class || (int) $notification->notifiable_id !== (int) $request->user()->id) {
            abort(403, 'Unauthorized access to notification');
        }

        $notification->delete();

        if ($request->header('X-Inertia')) {
            return back()->with('success', 'Notification supprimée.');
        }

        return response()->json([
            'message' => 'Notification deleted successfully',
        ]);
    }

    /**
     * Get the authenticated user's notification preferences.
     */
    public function preferences(Request $request): JsonResponse
    {
        $preferences = $this->preferenceService->getPreferences($request->user());

        return response()->json([
            'preferences' => $preferences,
            'supported_types' => NotificationPreferenceService::SUPPORTED_TYPES,
        ]);
    }

    /**
     * Update a notification preference for the authenticated user.
     */
    public function updatePreferences(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'notification_type' => ['required', 'string', 'in:'.implode(',', NotificationPreferenceService::SUPPORTED_TYPES)],
            'database_enabled' => ['required', 'boolean'],
            'email_enabled' => ['required', 'boolean'],
        ]);

        $preference = $this->preferenceService->updatePreference(
            $request->user(),
            $validated['notification_type'],
            $validated['database_enabled'],
            $validated['email_enabled']
        );

        return response()->json([
            'message' => 'Notification preference updated successfully',
            'preference' => $preference,
        ]);
    }
}
