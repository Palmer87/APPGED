<?php

namespace App\Http\Middleware;

use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Middleware;
use Spatie\Permission\PermissionRegistrar;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();

        $isTenantUser = $user instanceof User;

        if ($isTenantUser && $user->organization_id) {
            app(PermissionRegistrar::class)->setPermissionsTeamId($user->organization_id);
        }

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $isTenantUser ? [
                    'id' => $user->id,
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                    'name' => trim(($user->first_name ?? '').' '.($user->last_name ?? '')) ?: $user->email,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'job_title' => $user->job_title,
                    'avatar' => $user->avatar,
                    'status' => $user->status,
                    'organization_id' => $user->organization_id,
                ] : null,
                'organization' => ($isTenantUser && $user->organization) ? [
                    'id' => $user->organization->id,
                    'name' => $user->organization->name,
                    'slug' => $user->organization->slug ?? null,
                ] : null,
                'roles' => $isTenantUser ? $user->getRoleNames() : [],
                'permissions' => $isTenantUser ? $user->getAllPermissions()->pluck('name') : [],
                'unread_notifications_count' => ($isTenantUser && method_exists($user, 'unreadNotifications')) ? $user->unreadNotifications()->count() : 0,
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
                'message' => fn () => $request->session()->get('message'),
            ],
            'platform_auth' => fn () => $request->user('platform') ? [
                'user' => [
                    'id' => $request->user('platform')->id,
                    'name' => $request->user('platform')->name,
                    'email' => $request->user('platform')->email,
                    'role' => $request->user('platform')->role,
                ],
                'is_owner' => $request->user('platform')->isOwner(),
                'is_admin' => $request->user('platform')->isAdmin(),
                'is_support' => $request->user('platform')->isSupport(),
                'is_billing' => $request->user('platform')->isBilling(),
            ] : null,
            'url' => fn () => $request->getRequestUri(),
        ];
    }
}
