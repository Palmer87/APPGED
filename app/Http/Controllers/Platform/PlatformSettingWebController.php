<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Services\PlatformAuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PlatformSettingWebController extends Controller
{
    public function __construct(
        protected PlatformAuditService $auditService
    ) {}

    /**
     * Display platform global settings.
     */
    public function index(): Response
    {
        $settings = [
            'app_name' => config('app.name', 'GEDAPP'),
            'default_currency' => 'XOF',
            'trial_period_days' => 14,
            'support_email' => 'support@gedapp.com',
            'billing_email' => 'billing@gedapp.com',
            'system_maintenance' => false,
            'allow_new_registrations' => true,
        ];

        return Inertia::render('Platform/Settings/Index', [
            'settings' => $settings,
        ]);
    }

    /**
     * Update platform settings.
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'support_email' => ['required', 'email'],
            'billing_email' => ['required', 'email'],
            'trial_period_days' => ['required', 'integer', 'min:1', 'max:60'],
            'allow_new_registrations' => ['boolean'],
        ]);

        $this->auditService->log(
            action: 'platform.settings.updated',
            description: 'Paramètres globaux de la plateforme mis à jour.',
            newValues: $validated
        );

        return back()->with('success', 'Paramètres de la plateforme mis à jour.');
    }
}
