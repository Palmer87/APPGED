<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PricingWebController extends Controller
{
    /**
     * Display the public pricing page.
     */
    public function index(Request $request): Response
    {
        $plans = Plan::where('is_active', true)
            ->orderBy('sort_order')
            ->get()
            ->map(fn (Plan $plan) => [
                'id' => $plan->id,
                'name' => $plan->name,
                'slug' => $plan->slug,
                'description' => $plan->description,
                'monthly_price' => $plan->monthly_price,
                'annual_price' => $plan->annual_price,
                'currency' => $plan->currency,
                'annual_savings' => $plan->getAnnualSavings(),
                'max_users' => $plan->max_users,
                'max_storage_bytes' => $plan->max_storage_bytes,
                'max_directions' => $plan->max_directions,
                'max_document_types' => $plan->max_document_types,
                'max_ocr_pages_month' => $plan->max_ocr_pages_month,
                'has_api' => (bool) $plan->has_api,
                'has_workflows' => (bool) $plan->has_workflows,
                'has_advanced_audit' => (bool) $plan->has_advanced_audit,
                'has_priority_support' => (bool) $plan->has_priority_support,
                'has_dedicated_support' => (bool) $plan->has_dedicated_support,
                'has_sla' => (bool) $plan->has_sla,
                'has_custom_migration' => (bool) $plan->has_custom_migration,
                'has_custom_integrations' => (bool) $plan->has_custom_integrations,
                'is_custom' => (bool) $plan->is_custom,
                'sort_order' => $plan->sort_order,
            ]);

        return Inertia::render('Pricing/Index', [
            'plans' => $plans,
        ]);
    }
}
