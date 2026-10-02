<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Services\PlatformAuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class PlatformPlanWebController extends Controller
{
    public function __construct(
        protected PlatformAuditService $auditService
    ) {}

    /**
     * Display all pricing plans.
     */
    public function index(): Response
    {
        $plans = Plan::withCount('subscriptions')
            ->orderBy('sort_order')
            ->get();

        return Inertia::render('Platform/Plans/Index', [
            'plans' => $plans,
        ]);
    }

    /**
     * Show plan creation form.
     */
    public function create(): Response
    {
        return Inertia::render('Platform/Plans/Create');
    }

    /**
     * Store a newly created plan.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'slug' => ['nullable', 'string', 'max:50', 'unique:plans,slug'],
            'description' => ['nullable', 'string'],
            'monthly_price' => ['nullable', 'integer', 'min:0'],
            'annual_price' => ['nullable', 'integer', 'min:0'],
            'currency' => ['required', 'string', 'max:10'],
            'max_users' => ['nullable', 'integer', 'min:1'],
            'max_storage_gb' => ['nullable', 'numeric', 'min:0.1'],
            'max_directions' => ['nullable', 'integer', 'min:1'],
            'max_document_types' => ['nullable', 'integer', 'min:1'],
            'max_ocr_pages_month' => ['nullable', 'integer', 'min:0'],
            'has_api' => ['boolean'],
            'has_workflows' => ['boolean'],
            'has_advanced_audit' => ['boolean'],
            'has_priority_support' => ['boolean'],
            'is_active' => ['boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        if (empty($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['name']);
        }

        // Convert GB to bytes if provided
        if (isset($validated['max_storage_gb']) && $validated['max_storage_gb'] > 0) {
            $validated['max_storage_bytes'] = (int) round($validated['max_storage_gb'] * 1024 * 1024 * 1024);
        }
        unset($validated['max_storage_gb']);

        $plan = Plan::create($validated);

        $this->auditService->log(
            action: 'platform.plan.created',
            description: "Plan tarifaire '{$plan->name}' créé.",
            target: $plan,
            newValues: $plan->toArray()
        );

        return redirect()->route('platform.plans.index')
            ->with('success', "Le plan '{$plan->name}' a été créé avec succès.");
    }

    /**
     * Show plan edit form.
     */
    public function edit(Plan $plan): Response
    {
        return Inertia::render('Platform/Plans/Edit', [
            'plan' => $plan,
        ]);
    }

    /**
     * Update an existing plan.
     */
    public function update(Request $request, Plan $plan): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
            'monthly_price' => ['nullable', 'integer', 'min:0'],
            'annual_price' => ['nullable', 'integer', 'min:0'],
            'currency' => ['required', 'string', 'max:10'],
            'max_users' => ['nullable', 'integer', 'min:1'],
            'max_storage_gb' => ['nullable', 'numeric', 'min:0.1'],
            'max_directions' => ['nullable', 'integer', 'min:1'],
            'max_document_types' => ['nullable', 'integer', 'min:1'],
            'max_ocr_pages_month' => ['nullable', 'integer', 'min:0'],
            'has_api' => ['boolean'],
            'has_workflows' => ['boolean'],
            'has_advanced_audit' => ['boolean'],
            'has_priority_support' => ['boolean'],
            'is_active' => ['boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        if (isset($validated['max_storage_gb']) && $validated['max_storage_gb'] > 0) {
            $validated['max_storage_bytes'] = (int) round($validated['max_storage_gb'] * 1024 * 1024 * 1024);
        }
        unset($validated['max_storage_gb']);

        $old = $plan->toArray();
        $plan->update($validated);

        $this->auditService->log(
            action: 'platform.plan.updated',
            description: "Plan tarifaire '{$plan->name}' mis à jour.",
            target: $plan,
            oldValues: $old,
            newValues: $plan->toArray()
        );

        return redirect()->route('platform.plans.index')
            ->with('success', "Le plan '{$plan->name}' a été mis à jour avec succès.");
    }

    /**
     * Toggle active status of a plan.
     */
    public function toggleActive(Plan $plan): RedirectResponse
    {
        $newStatus = ! $plan->is_active;
        $plan->update(['is_active' => $newStatus]);

        $action = $newStatus ? 'platform.plan.enabled' : 'platform.plan.disabled';
        $this->auditService->log(
            action: $action,
            description: "Plan tarifaire '{$plan->name}' ".($newStatus ? 'activé' : 'désactivé').'.',
            target: $plan
        );

        return back()->with('success', "Statut du plan '{$plan->name}' modifié.");
    }
}
