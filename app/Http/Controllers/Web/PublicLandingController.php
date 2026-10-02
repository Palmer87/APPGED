<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Models\Lead;
use App\Models\Organization;
use App\Models\Plan;
use Database\Seeders\PlanSeeder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PublicLandingController extends Controller
{
    /**
     * Display the SaaS landing page.
     */
    public function index(Request $request): Response
    {
        if (Plan::where('is_active', true)->count() === 0) {
            (new PlanSeeder)->run();
        }

        $plans = Plan::where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        $stats = [
            'total_organizations' => Organization::count(),
            'total_documents' => Document::count(),
        ];

        return Inertia::render('Welcome', [
            'plans' => $plans,
            'stats' => $stats,
        ]);
    }

    /**
     * Display the detailed features page.
     */
    public function features(): Response
    {
        return Inertia::render('Public/Features');
    }

    /**
     * Display the contact page.
     */
    public function contact(): Response
    {
        return Inertia::render('Public/Contact');
    }

    /**
     * Process contact form submission.
     */
    public function storeContact(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255'],
            'subject' => ['required', 'string', 'max:150'],
            'message' => ['required', 'string', 'min:10', 'max:3000'],
        ]);

        Lead::create([
            'name' => $validated['name'],
            'company' => 'Contact Form',
            'email' => $validated['email'],
            'needs' => $validated['subject'],
            'message' => $validated['message'],
            'ip_address' => $request->ip(),
            'status' => 'new',
            'metadata' => ['source' => 'public_contact'],
        ]);

        return back()
            ->with('success', 'Votre message a bien été envoyé. Notre équipe vous répondra dans les plus brefs délais.')
            ->with('message', 'Votre message a bien été envoyé.');
    }
}
