<?php

namespace App\Providers;

use App\Models\Document;
use App\Models\Group;
use App\Models\Organization;
use App\Models\User;
use App\Policies\DocumentPolicy;
use App\Policies\GroupPolicy;
use App\Policies\OrganizationPolicy;
use App\Policies\UserPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     */
    protected $policies = [
        // Register model policies here
        User::class => UserPolicy::class,
        Group::class => GroupPolicy::class,
        Organization::class => OrganizationPolicy::class,
        Document::class => DocumentPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();

        // Super‑admin has global access across all organisations
        Gate::before(function (User $user, $ability) {
            if ($user->hasRole('super-admin')) {
                return true; // grant all abilities
            }
        });
    }
}
