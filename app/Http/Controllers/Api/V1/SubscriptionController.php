<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\ChangePlanRequest;
use App\Http\Resources\Api\V1\SubscriptionResource;
use App\Http\Resources\Api\V1\SubscriptionUsageResource;
use App\Models\Organization;
use App\Models\Plan;
use App\Models\Subscription;
use App\Services\BillingService;
use App\Services\SubscriptionUsageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class SubscriptionController extends Controller
{
    public function __construct(
        protected BillingService $billingService,
        protected SubscriptionUsageService $usageService
    ) {}

    /**
     * Get current organization subscription.
     */
    public function show(Request $request): SubscriptionResource
    {
        $user = $request->user();
        $org = Organization::findOrFail($user->organization_id);

        $subscription = $this->billingService->getCurrentSubscription($org);
        Gate::authorize('view', $subscription ?? Subscription::class);

        return new SubscriptionResource($subscription);
    }

    /**
     * Get current subscription usage and limits.
     */
    public function usage(Request $request): SubscriptionUsageResource
    {
        $user = $request->user();
        $org = Organization::findOrFail($user->organization_id);

        $subscription = $this->billingService->getCurrentSubscription($org);
        Gate::authorize('view', $subscription ?? Subscription::class);

        $usage = $this->usageService->getUsage($org, $subscription?->plan);

        return new SubscriptionUsageResource($usage);
    }

    /**
     * Change subscription plan or cycle.
     */
    public function changePlan(ChangePlanRequest $request): JsonResponse
    {
        $user = $request->user();
        $org = Organization::findOrFail($user->organization_id);

        $subscription = $this->billingService->getCurrentSubscription($org);
        Gate::authorize('manage', $subscription ?? Subscription::class);

        $validated = $request->validated();

        $plan = isset($validated['plan_id'])
            ? Plan::findOrFail($validated['plan_id'])
            : Plan::where('slug', $validated['plan_slug'])->firstOrFail();

        $updated = $this->billingService->changePlan($org, $plan, $validated['billing_cycle'], $user);

        return response()->json([
            'message' => "Abonnement modifié avec succès vers le plan {$plan->name}.",
            'data' => new SubscriptionResource($updated),
        ]);
    }

    /**
     * Cancel subscription.
     */
    public function cancel(Request $request): JsonResponse
    {
        $user = $request->user();
        $org = Organization::findOrFail($user->organization_id);

        $subscription = $this->billingService->getCurrentSubscription($org);
        Gate::authorize('manage', $subscription ?? Subscription::class);

        $cancelled = $this->billingService->cancelSubscription($org, false, $user);

        return response()->json([
            'message' => 'Renouvellement automatique désactivé avec succès.',
            'data' => new SubscriptionResource($cancelled),
        ]);
    }

    /**
     * Resume subscription.
     */
    public function resume(Request $request): JsonResponse
    {
        $user = $request->user();
        $org = Organization::findOrFail($user->organization_id);

        $subscription = $this->billingService->getCurrentSubscription($org);
        Gate::authorize('manage', $subscription ?? Subscription::class);

        $resumed = $this->billingService->resumeSubscription($org, $user);

        return response()->json([
            'message' => 'Abonnement réactivé avec succès.',
            'data' => new SubscriptionResource($resumed),
        ]);
    }
}
