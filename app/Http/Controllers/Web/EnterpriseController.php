<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EnterpriseController extends Controller
{
    /**
     * Display the Enterprise solution and demo request page.
     */
    public function index(Request $request): Response
    {
        $prefill = [
            'name' => $request->session()->get('registration.admin.first_name', '').' '.$request->session()->get('registration.admin.last_name', ''),
            'email' => $request->session()->get('registration.admin.email', ''),
            'company' => $request->session()->get('registration.organization.name', ''),
            'phone' => $request->session()->get('registration.admin.phone', ''),
        ];

        return Inertia::render('Public/Enterprise', [
            'prefill' => array_filter($prefill),
        ]);
    }

    /**
     * Store an enterprise sales demo lead.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'company' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'estimated_users' => ['nullable', 'string', 'max:50'],
            'needs' => ['nullable', 'string', 'max:255'],
            'message' => ['nullable', 'string', 'max:3000'],
        ]);

        Lead::create([
            'name' => $validated['name'],
            'company' => $validated['company'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'estimated_users' => $validated['estimated_users'] ?? null,
            'needs' => $validated['needs'] ?? null,
            'message' => $validated['message'] ?? null,
            'ip_address' => $request->ip(),
            'status' => 'new',
            'metadata' => [
                'source' => 'enterprise_demo_request',
                'user_agent' => $request->userAgent(),
            ],
        ]);

        return back()
            ->with('success', 'Merci pour votre intérêt ! Notre équipe grands comptes vous contactera sous 24h ouvrées pour organiser votre démonstration personnalisée.')
            ->with('message', 'Demande de démonstration envoyée avec succès.');
    }
}
