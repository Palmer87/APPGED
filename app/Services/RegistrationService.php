<?php

namespace App\Services;

use App\Models\Organization;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Database\Seeders\PlanSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RegistrationService
{
    /**
     * Standard administrator permissions for an organization.
     *
     * @var array<int, string>
     */
    protected const ADMIN_PERMISSIONS = [
        'documents.view', 'documents.create', 'documents.update', 'documents.delete', 'documents.download', 'documents.share', 'documents.archive', 'documents.restore',
        'folders.view', 'folders.create', 'folders.update', 'folders.delete', 'folders.share',
        'users.view', 'users.create', 'users.update', 'users.delete',
        'roles.view', 'roles.create', 'roles.update', 'roles.delete',
        'groups.view', 'groups.create', 'groups.update', 'groups.delete',
        'categories.view', 'categories.create', 'categories.update', 'categories.delete',
        'tags.view', 'tags.create', 'tags.update', 'tags.delete',
        'metadata.view', 'metadata.create', 'metadata.update', 'metadata.delete',
        'settings.view', 'settings.update',
        'audit.view',
        'workflows.view', 'workflows.create', 'workflows.update', 'workflows.delete', 'workflows.execute', 'workflows.approve', 'workflows.reject', 'workflows.cancel',
        'comments.view', 'comments.create', 'comments.update', 'comments.delete', 'comments.moderate',
        'billing.view', 'billing.manage',
    ];

    public function __construct(
        protected ?AuditService $auditService = null
    ) {
        $this->auditService = $this->auditService ?? app(AuditService::class);
    }

    /**
     * Atomically register an organization, its primary administrator user, admin role, and subscription.
     *
     * @param  array{name: string, activity?: ?string, country?: ?string, city?: ?string}  $orgData
     * @param  array{first_name: string, last_name: string, email: string, phone?: ?string, password: string}  $adminData
     * @return array{organization: Organization, user: User, subscription: Subscription, plan: Plan}
     *
     * @throws ValidationException
     */
    public function register(array $orgData, array $adminData, string $planSlug, string $billingCycle): array
    {
        // Prevent Organization boot hook from triggering default subscription
        Organization::$createDefaultSubscriptionOnBoot = false;

        try {
            return DB::transaction(function () use ($orgData, $adminData, $planSlug, $billingCycle) {
                // Verify email uniqueness inside the transaction (concurrency/double submission safety)
                if (User::query()->where('email', $adminData['email'])->exists()) {
                    throw ValidationException::withMessages([
                        'email' => ['Cette adresse email est déjà enregistrée.'],
                    ]);
                }

                // Retrieve chosen plan
                $plan = Plan::query()->where('slug', $planSlug)->first();
                if (! $plan) {
                    $plan = Plan::query()->where('slug', 'essential')->first();
                }
                if (! $plan) {
                    (new PlanSeeder)->run();
                    $plan = Plan::query()->where('slug', $planSlug)->first()
                        ?? Plan::query()->where('slug', 'essential')->first();
                }

                // Generate unique slug for organization
                $baseSlug = Str::slug($orgData['name']);
                if ($baseSlug === '') {
                    $baseSlug = 'organisation';
                }
                $slug = $baseSlug;
                $count = 1;
                while (Organization::query()->where('slug', $slug)->exists()) {
                    $count++;
                    $slug = "{$baseSlug}-{$count}";
                }

                // 1. Create Organization
                $organization = Organization::create([
                    'name' => $orgData['name'],
                    'slug' => $slug,
                    'activity' => $orgData['activity'] ?? null,
                    'country' => $orgData['country'] ?? null,
                    'city' => $orgData['city'] ?? null,
                    'storage_limit' => $plan->max_storage_bytes ?? 5_368_709_120,
                    'status' => 'active',
                ]);

                // 2. Create primary administrator User bound to this organization
                $user = User::create([
                    'organization_id' => $organization->id,
                    'first_name' => $adminData['first_name'],
                    'last_name' => $adminData['last_name'],
                    'email' => $adminData['email'],
                    'phone' => $adminData['phone'] ?? null,
                    'password' => Hash::make($adminData['password']),
                    'status' => 'active',
                ]);

                // 3. Setup Spatie Permission roles & assign 'admin' role to user
                app(PermissionRegistrar::class)->setPermissionsTeamId($organization->id);

                foreach (self::ADMIN_PERMISSIONS as $perm) {
                    Permission::findOrCreate($perm, 'web');
                }

                $adminRole = Role::findOrCreate('admin', 'web');
                $adminRole->syncPermissions(self::ADMIN_PERMISSIONS);
                $user->assignRole($adminRole);

                app(PermissionRegistrar::class)->setPermissionsTeamId(null);

                // 4. Create Subscription
                $now = now();
                $isEnterprise = $plan->slug === 'enterprise';

                if ($isEnterprise) {
                    // Enterprise: pending contact status without immediate trial billing charges
                    $subscription = Subscription::create([
                        'organization_id' => $organization->id,
                        'plan_id' => $plan->id,
                        'billing_cycle' => $billingCycle,
                        'status' => 'pending',
                        'starts_at' => $now,
                        'auto_renew' => false,
                        'provider' => 'manual',
                        'metadata' => [
                            'enterprise_quote_requested' => true,
                            'requested_at' => $now->toIso8601String(),
                            'source' => 'saas_registration',
                        ],
                    ]);
                } else {
                    // Standard plans: 14-day trial
                    $trialEndsAt = (clone $now)->addDays(14);
                    $subscription = Subscription::create([
                        'organization_id' => $organization->id,
                        'plan_id' => $plan->id,
                        'billing_cycle' => $billingCycle,
                        'status' => 'trialing',
                        'starts_at' => $now,
                        'trial_starts_at' => $now,
                        'trial_ends_at' => $trialEndsAt,
                        'current_period_starts_at' => $now,
                        'current_period_ends_at' => $trialEndsAt,
                        'auto_renew' => true,
                        'provider' => 'manual',
                    ]);
                }

                // 5. Audit Log
                $this->auditService->success(
                    action: 'registration.saas',
                    auditable: $organization,
                    user: $user,
                    description: "Organisation '{$organization->name}' et administrateur '{$user->email}' créés avec succès."
                );

                return [
                    'organization' => $organization,
                    'user' => $user,
                    'subscription' => $subscription,
                    'plan' => $plan,
                ];
            });
        } finally {
            Organization::$createDefaultSubscriptionOnBoot = true;
        }
    }
}
