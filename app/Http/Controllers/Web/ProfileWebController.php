<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\NotificationPreferenceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProfileWebController extends Controller
{
    public function __construct(
        protected NotificationPreferenceService $preferenceService
    ) {}

    public function index(Request $request): Response
    {
        $user = $request->user();
        $preferences = $this->preferenceService->getPreferences($user);

        return Inertia::render('Profile/Index', [
            'user' => [
                'id' => $user->id,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'email' => $user->email,
                'phone' => $user->phone,
                'job_title' => $user->job_title,
                'status' => $user->status,
                'roles' => $user->getRoleNames(),
                'organization' => $user->organization ? [
                    'id' => $user->organization->id,
                    'name' => $user->organization->name,
                ] : null,
            ],
            'preferences' => $preferences,
            'supportedTypes' => NotificationPreferenceService::SUPPORTED_TYPES,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:30'],
            'job_title' => ['nullable', 'string', 'max:100'],
        ]);

        $user->update($validated);

        return back()->with('success', 'Profil mis à jour avec succès.');
    }

    public function updatePreferences(Request $request): RedirectResponse
    {
        $user = $request->user();

        $request->validate([
            'preferences' => ['required', 'array'],
            'preferences.*.notification_type' => ['required', 'string', 'in:'.implode(',', NotificationPreferenceService::SUPPORTED_TYPES)],
            'preferences.*.database_enabled' => ['required', 'boolean'],
            'preferences.*.email_enabled' => ['required', 'boolean'],
        ]);

        foreach ($request->input('preferences') as $pref) {
            $this->preferenceService->updatePreference(
                $user,
                $pref['notification_type'],
                (bool) $pref['database_enabled'],
                (bool) $pref['email_enabled']
            );
        }

        return back()->with('success', 'Préférences de notification mises à jour.');
    }
}
